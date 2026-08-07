<!DOCTYPE html>
<html>
<head>
    <title>Test Configurator</title>
</head>
<body>
    <h1>Test Band Configurator</h1>
    <button onclick="ProductModal.open('4T18M')">Open 4T18M Configurator</button>
    
    <?php 
    require_once 'classes/ProductModal.php';
    ProductModal::renderModalContainer();
    ?>
</body>
</html>