// JavaScript para o plugin de doações

document.addEventListener('DOMContentLoaded', function() {
    // Exemplo de funcionalidade: exibir uma mensagem de sucesso após o envio do feedback
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('feedback') && urlParams.get('feedback') === 'sucesso') {
        alert('Seu feedback foi enviado com sucesso!');
    }
});
