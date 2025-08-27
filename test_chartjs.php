<?php
$pageTitle = 'Chart.js Test';
$currentPage = 'test';
ob_start();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Chart.js Loading Test</h3>
            </div>
            <div class="card-body">
                <div id="chartStatus">Checking Chart.js...</div>
                <canvas id="testChart" style="height: 300px; margin-top: 20px;"></canvas>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = "
    // Test Chart.js loading
    console.log('Test page loaded');
    console.log('Chart.js available:', typeof Chart);
    
    const statusDiv = document.getElementById('chartStatus');
    if (typeof Chart !== 'undefined') {
        statusDiv.innerHTML = '<div class=\"alert alert-success\">✅ Chart.js is loaded successfully!</div>';
        
        // Create a simple test chart
        const ctx = document.getElementById('testChart').getContext('2d');
        const testChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
                datasets: [{
                    label: 'Test Data',
                    data: [12, 19, 3, 5, 2],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Chart.js Test Chart'
                    }
                }
            }
        });
        
        console.log('Test chart created:', testChart);
    } else {
        statusDiv.innerHTML = '<div class=\"alert alert-danger\">❌ Chart.js is NOT loaded!</div>';
        console.error('Chart.js not available');
    }
";

$content = ob_get_clean();
require 'admin/layout.php';
?>