<!DOCTYPE html>
<html>
<head>
    <title>Test Bracelets Search</title>
</head>
<body>
    <h1>Testing Bracelets Search Fix</h1>
    
    <button onclick="testBraceletSearch()">Test Bracelets Search</button>
    <div id="result"></div>
    
    <script>
    function testBraceletSearch() {
        console.log('Testing bracelets search...');
        
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=search&term=bracelets'
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            document.getElementById('result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            
            if (data.type === 'keyword_match') {
                console.log('✅ Keyword match detected');
                console.log('Indexes:', data.indexes);
                console.log('Files:', data.files);
            } else {
                console.log('❌ Unexpected type:', data.type);
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            document.getElementById('result').innerHTML = 'Error: ' + error.message;
        });
    }
    </script>
</body>
</html>
<?php
// Handle AJAX requests
if (isset($_POST['action']) && $_POST['action'] === 'search') {
    require_once 'classes/CatalogSearch.php';
    $catalogSearch = new CatalogSearch();
    $catalogSearch->handleAjaxRequest();
    exit;
}
?>