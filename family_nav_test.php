<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" href="styles.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Family Collection Navigation Test - Cadman Manufacturing</title>
</head>
<body>
    <?php 
    echo "<!-- Before navigation include -->\n";
    include 'navigation.php'; 
    echo "<!-- After navigation include -->\n";
    renderNavigation('family'); 
    echo "<!-- After renderNavigation -->\n";
    ?>
    <?php 
    echo "<!-- Before topButton include -->\n";
    include 'topButton.php'; 
    echo "<!-- After topButton include -->\n";
    renderTopButton(); 
    echo "<!-- After renderTopButton -->\n";
    ?>
    
    <h1>Navigation + TopButton Test Complete</h1>
</body>
</html>
