<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#FFD700" />
<meta name="keywords" content="Mother daughter jewelry, family jewelry sets, Cadman Manufacturing" />
<link rel="icon" sizes="" href="favicon.ico">
<title>Mother & Daughters Collection - Cadman Manufacturing</title>
<script src="js/jquery-3.6.0.min.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; renderNavigation('mother_and_daughters'); ?>
    <?php include 'topButton.php'; renderTopButton(); ?>
    
    <!-- Collection Header -->
    <div class="mother-daughters-header">
        <div class="collection-header">
            <h1>Mother & Daughters Collection</h1>
            <p>Celebrate the special bond between mothers and daughters with our matching jewelry sets. These pieces are designed to symbolize the eternal connection and shared memories between generations.</p>
        </div>
    </div>
    
    <!-- Coming Soon Message -->
    <div class="gallery-container">
        <div style="text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.95); margin: 20px; border-radius: 10px;">
            <h2 style="color: #FFD700; margin-bottom: 20px;">Collection Coming Soon</h2>
            <p style="color: #666; font-size: 18px; max-width: 600px; margin: 0 auto 30px auto; line-height: 1.6;">
                We're currently developing our Mother & Daughters collection featuring matching jewelry sets and 
                complementary pieces that celebrate family bonds. Please contact us for custom family jewelry designs.
            </p>
            <a href="#formtable" class="view-details-btn" style="font-size: 16px; padding: 12px 30px;">
                Contact Us for Family Jewelry
            </a>
        </div>
    </div>
    
    <script>
    // Filter functionality placeholder
    function filterItems(category) {
        // Placeholder for future expansion
    }
    </script>

    <?php 
    include 'footer.php'; 
    renderFooter('mother_daughters');
    ?>
</body>
</html>
