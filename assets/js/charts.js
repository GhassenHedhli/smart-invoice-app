// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const chartData = window.revenueData || {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Revenue',
                data: [0, 0, 0, 0, 0, 0],
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0 // ⬅ sharp zigzag effect
            }]
        };

        new Chart(revenueCtx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Revenue Trend'
                    }
                },
                elements: {
                    point: {
                        radius: 5, // makes data points more visible
                        backgroundColor: '#0d6efd'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    }
                }
            }
        });
    }
});
