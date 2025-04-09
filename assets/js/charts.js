/**
 * Módulo de gráficos para o dashboard
 * Gerencia os gráficos de velocidade e outros dados
 */

let speedChart = null;
const chartData = {
    labels: [],
    datasets: [{
        label: 'Velocidade (km/h)',
        data: [],
        borderColor: '#30d158',
        backgroundColor: 'rgba(48, 209, 88, 0.2)',
        borderWidth: 2,
        tension: 0.3,
        fill: true
    }]
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: {
            display: true,
            title: {
                display: false
            },
            grid: {
                color: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: '#f5f5f7',
                maxTicksLimit: 10
            }
        },
        y: {
            display: true,
            title: {
                display: true,
                text: 'Velocidade (km/h)',
                color: '#f5f5f7'
            },
            suggestedMin: 0,
            suggestedMax: 150,
            grid: {
                color: 'rgba(255, 255, 255, 0.1)'
            },
            ticks: {
                color: '#f5f5f7'
            }
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(0, 0, 0, 0.7)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: '#30d158',
            borderWidth: 1
        }
    },
    animation: {
        duration: 500
    },
    interaction: {
        mode: 'nearest',
        axis: 'x',
        intersect: false
    }
};

function initializeCharts() {
    const ctx = document.getElementById('speedChart').getContext('2d');
    
    // Configurar dados iniciais (últimos 30 segundos, zero velocidade)
    const now = new Date();
    for (let i = 30; i >= 0; i--) {
        const time = new Date(now - i * 1000);
        chartData.labels.push(time.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }));
        chartData.datasets[0].data.push(0);
    }
    
    // Criar gráfico
    speedChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: chartOptions
    });
}

function updateSpeedChart(speed) {
    // Adicionar novo ponto de dados
    const now = new Date();
    const timeStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    
    chartData.labels.push(timeStr);
    chartData.datasets[0].data.push(speed);
    
    // Remover ponto mais antigo se exceder 30 pontos
    if (chartData.labels.length > 30) {
        chartData.labels.shift();
        chartData.datasets[0].data.shift();
    }
    
    // Atualizar gráfico
    speedChart.update();
}