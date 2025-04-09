/**
 * Módulo de controles específicos da interface do usuário
 * Lida com interações avançadas e animações de UI
 */

document.addEventListener('DOMContentLoaded', function() {
    // Efeito de iluminação nos botões ao pressionar
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('mousedown', function() {
            this.classList.add('pressed');
        });
        
        button.addEventListener('mouseup', function() {
            this.classList.remove('pressed');
        });
        
        button.addEventListener('mouseleave', function() {
            this.classList.remove('pressed');
        });
        
        // Para dispositivos touch
        button.addEventListener('touchstart', function(e) {
            e.preventDefault();
            this.classList.add('pressed');
        });
        
        button.addEventListener('touchend', function() {
            this.classList.remove('pressed');
        });
    });
    
    // Efeito de pulso para botão de emergência
    const emergencyBtn = document.getElementById('emergencyBtn');
    if (emergencyBtn) {
        setInterval(function() {
            emergencyBtn.classList.add('pulse');
            setTimeout(function() {
                emergencyBtn.classList.remove('pulse');
            }, 1000);
        }, 3000);
    }
    
    // Adicionar destaque ao slider quando em uso
    const throttle = document.getElementById('throttle');
    if (throttle) {
        throttle.addEventListener('input', function() {
            this.classList.add('active');
        });
        
        throttle.addEventListener('change', function() {
            setTimeout(() => {
                this.classList.remove('active');
            }, 500);
        });
    }
    
    // Atualizar valores numéricos com efeito de contador
    function animateCounter(element, startValue, endValue, duration) {
        const startTime = performance.now();
        const difference = endValue - startValue;
        
        function updateCounter(currentTime) {
            const elapsedTime = currentTime - startTime;
            
            if (elapsedTime < duration) {
                const progress = elapsedTime / duration;
                const currentValue = Math.round(startValue + (difference * progress));
                element.textContent = currentValue;
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = endValue;
            }
        }
        
        requestAnimationFrame(updateCounter);
    }
    
    // Expor a função de animação para uso global
    window.animateSpeedDisplay = function(startValue, endValue) {
        const speedValue = document.getElementById('speedValue');
        animateCounter(speedValue, startValue, endValue, 500);
    };
});