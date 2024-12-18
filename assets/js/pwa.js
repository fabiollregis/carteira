// Registra o Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/carteira/sw.js')
            .then((registration) => {
                console.log('ServiceWorker registrado com sucesso:', registration.scope);
            })
            .catch((error) => {
                console.log('Falha ao registrar o ServiceWorker:', error);
            });
    });
}

// Adiciona o banner de instalação personalizado
let deferredPrompt;
const installButton = document.createElement('button');
installButton.style.display = 'none';
installButton.classList.add('btn', 'btn-success', 'install-button');
installButton.textContent = 'Instalar Aplicativo';

window.addEventListener('beforeinstallprompt', (e) => {
    // Previne o banner padrão do Chrome
    e.preventDefault();
    // Guarda o evento para usar depois
    deferredPrompt = e;
    // Atualiza a UI notificando o usuário que pode instalar o PWA
    installButton.style.display = 'block';

    installButton.addEventListener('click', (e) => {
        // Esconde o botão de instalação
        installButton.style.display = 'none';
        // Mostra o prompt de instalação
        deferredPrompt.prompt();
        // Espera o usuário responder ao prompt
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('Usuário aceitou a instalação do PWA');
            } else {
                console.log('Usuário recusou a instalação do PWA');
            }
            deferredPrompt = null;
        });
    });
});
