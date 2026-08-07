<?php
/**
 * Reconcile metal weights across product_variants.
 *
 *  Phase A: existing variant has its weight column = 0 -> derive from a sibling
 *           via density ratio.
 *  Phase B: product is missing a karat variant -> INSERT new row with derived
 *           weight. If only STER exists, create 10K + 14K + 18K all at once.
 *  Phase C: product has no usable weight in any sibling -> report only.
 *
 *  GF rows: backfill sterling_grams like STER, but we do NOT auto-create GF.
 *
 *  Density ratios (gold:silver mass for equal volume):
 *      10K / 10KTT = 1.117   14K = 1.262   18K = 1.504
 *
 *  Usage:
 *      php scripts/reconcile_metal_weights.php
 *      php scripts/reconcile_metal_weights.php --product 21TG
 *      php scripts/reconcile_metal_weights.php --csv > out.csv
 *      php scripts/reconcile_metal_weights.php --apply
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only\n"); }
require_once __DIR__ . '/../includes/db_config.php';

$apply       = in_array('--apply', $argv, true);
$csvMode     = in_array('--csv',   $argv, true);
$onlyProduct = null;
foreach ($argv as $i => $a) {
    if ($a === '--product' && isset($argv[$i + 1])) $onlyProduct = $argv[$i + 1];
}

const RATIO = ['10K' => 1.117, '10KTT' => 1.117, '14K' => 1.262, '18K' => 1.504];
const GOLD_METALS = ['10K', '10KTT', '14K', '18K'];

try {
    $pdo = getDBConnection();
} catch (Throwable $e) { fwrite(STDERR, "DB: ".$e->getMessage()."\n"); exit(2); }

$where = $onlyProduct ? "WHERE p.base_code = :bc" : "";
$stmt = $pdo->prepare("
    SELECT p.product_id pid, p.base_code, pv.variant_id, pv.full_item_code,
           pv.metal_type, pv.metal_variant, pv.gold_grams, pv.sterling_grams,
           pv.material_cost
    FROM products p
    LEFT JOIN product_variants pv ON pv.product_id = p.product_id
    $where
    ORDER BY p.base_code, FIELD(pv.metal_type,'STER','GF','10K','10KTT','14K','18K')
");
$stmt->execute($onlyProduct ? [':bc' => $onlyProduct] : []);

$products = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($r['variant_id'] === null) continue;
    $products[$r['base_code']]['pid'] = (int)$r['pid'];
    $products[$r['base_code']]['variants'][] = $r;
}

$phaseA = $phaseB = $phaseC = [];

foreach ($products as $base => $info) {
    $byMetal = [];
    foreach ($info['variants'] as $v) $byMetal[$v['metal_type']][] = $v;

    // pick best sterling sibling (STER preferred, GF acceptable)
    $bestSter = null;
    foreach (['STER', 'GF'] as $m) {
        foreach ($byMetal[$m] ?? [] as $v)
            if ((float)$v['sterling_grams'] > 0) { $bestSter = $v; break 2; }
    }
    // pick best gold sibling (lightest karat first)
    $bestGold = null;
    foreach (GOLD_METALS as $m) {
        foreach ($byMetal[$m] ?? [] as $v)
            if ((float)$v['gold_grams'] > 0) { $bestGold = $v; break 2; }
    }

    // PHASE A: backfill missing weight on existing rows
    foreach ($info['variants'] as $v) {
        $m = $v['metal_type'];
        if (in_array($m, GOLD_METALS, true) && (float)$v['gold_grams'] == 0 && $bestSter) {
            $newG = round((float)$bestSter['sterling_grams'] * RATIO[$m], 3);
            $phaseA[] = ['vid'=>$v['variant_id'],'base'=>$base,'metal'=>$m,
                'field'=>'gold_grams','old'=>$v['gold_grams'],'new'=>$newG,
                'src'=>$bestSter['metal_type'].' '.$bestSter['sterling_grams'].'g'];
        } elseif (($m === 'STER' || $m === 'GF') && (float)$v['sterling_grams'] == 0 && $bestGold) {
            $sm   = $bestGold['metal_type'];
            $newS = round((float)$bestGold['gold_grams'] / RATIO[$sm], 3);
            $phaseA[] = ['vid'=>$v['variant_id'],'base'=>$base,'metal'=>$m,
                'field'=>'sterling_grams','old'=>$v['sterling_grams'],'new'=>$newS,
                'src'=>$sm.' '.$bestGold['gold_grams'].'g'];
        }
    }

    // PHASE B: create missing sister variants
    $hasSter = !empty($byMetal['STER']);
    if (!$hasSter && $bestGold) {
        $sm   = $bestGold['metal_type'];
        $newS = round((float)$bestGold['gold_grams'] / RATIO[$sm], 3);
        $phaseB[] = ['base'=>$base,'pid'=>$info['pid'],'new_metal'=>'STER',
            'gold_grams'=>0,'sterling_grams'=>$newS,
            'material_cost'=>$bestGold['material_cost'],
            'full_item_code'=>$base.'/STER','src'=>$sm];
    }
    // if product has STER (or GF) weight, create each missing karat
    if ($bestSter) {
        foreach (['10K', '14K', '18K'] as $k) {
            if (empty($byMetal[$k])) {
                $newG = round((float)$bestSter['sterling_grams'] * RATIO[$k], 3);
                $phaseB[] = ['base'=>$base,'pid'=>$info['pid'],'new_metal'=>$k,
                    'gold_grams'=>$newG,'sterling_grams'=>0,
                    'material_cost'=>$bestSter['material_cost'],
                    'full_item_code'=>$base.'/'.$k,'src'=>$bestSter['metal_type']];
            }
        }
    }

    // PHASE C: nothing to derive from
    if (!$bestSter && !$bestGold) {
        $phaseC[] = ['base'=>$base,'reason'=>'no variant has any weight'];
    }
}

// ----- output -----
if ($csvMode) {
    $out = fopen('php://stdout','w');
    fputcsv($out,['phase','base_code','metal','action','old','new','source']);
    foreach ($phaseA as $r) fputcsv($out,['A',$r['base'],$r['metal'],'update '.$r['field'],$r['old'],$r['new'],$r['src']]);
    foreach ($phaseB as $r) fputcsv($out,['B',$r['base'],$r['new_metal'],'insert','','gold='.$r['gold_grams'].' ster='.$r['sterling_grams'],'from '.$r['src']]);
    foreach ($phaseC as $r) fputcsv($out,['C',$r['base'],'','flag','','',$r['reason']]);
    fclose($out);
} else {
    $bar = str_repeat('=',76)."\n";
    echo $bar."PHASE A: backfill missing weights (".count($phaseA).")\n".$bar;
    foreach ($phaseA as $r)
        printf("  %-14s %-6s %-15s %8s -> %-8s (from %s)\n",
            $r['base'],$r['metal'],$r['field'],$r['old'],$r['new'],$r['src']);

    echo "\n".$bar."PHASE B: create missing variants (".count($phaseB).")\n".$bar;
    foreach ($phaseB as $r)
        printf("  %-14s NEW %-6s gold=%-8s ster=%-8s (from %s)\n",
            $r['base'],$r['new_metal'],$r['gold_grams'],$r['sterling_grams'],$r['src']);

    echo "\n".$bar."PHASE C: flagged - no weight anywhere (".count($phaseC).")\n".$bar;
    foreach ($phaseC as $r) printf("  %-14s %s\n",$r['base'],$r['reason']);

    echo "\nSummary: ".count($phaseA)." updates, ".count($phaseB)." inserts, "
        .count($phaseC)." flagged.\n";
    echo $apply ? "Mode: APPLY\n" : "Mode: DRY-RUN (pass --apply to write)\n";
}

if (!$apply) exit(0);

// ----- apply -----
$pdo->beginTransaction();
try {
    $updG = $pdo->prepare("UPDATE product_variants SET gold_grams=:v WHERE variant_id=:id");
    $updS = $pdo->prepare("UPDATE product_variants SET sterling_grams=:v WHERE variant_id=:id");
    foreach ($phaseA as $r) {
        ($r['field']==='gold_grams' ? $updG : $updS)->execute([':v'=>$r['new'],':id'=>$r['vid']]);
    }

    $chk = $pdo->prepare("SELECT 1 FROM product_variants WHERE full_item_code=?");
    $ins = $pdo->prepare("
        INSERT INTO product_variants
            (product_id, full_item_code, metal_type, gold_grams, sterling_grams,
             material_cost, gold_cost, sterling_cost, total_cost, selling_price)
        VALUES (:pid,:code,:m,:g,:s,:mc,0,0,0,0)
    ");
    $ok = 0; $skip = 0;
    foreach ($phaseB as $r) {
        $chk->execute([$r['full_item_code']]);
        if ($chk->fetchColumn()) { $skip++; continue; }
        $ins->execute([':pid'=>$r['pid'],':code'=>$r['full_item_code'],
            ':m'=>$r['new_metal'],':g'=>$r['gold_grams'],
            ':s'=>$r['sterling_grams'],':mc'=>$r['material_cost']]);
        $ok++;
    }
    $pdo->commit();
    echo "\nApplied: ".count($phaseA)." updates, $ok inserts ($skip dup skipped).\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR,"ERROR: ".$e->getMessage()." -- ROLLED BACK\n"); exit(3);
}
