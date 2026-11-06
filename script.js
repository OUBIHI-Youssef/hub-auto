// calcul du prix au litre a la volee
document.addEventListener('DOMContentLoaded', () => {
    const inputLitres = document.getElementById('input-litres');
    const inputPrix = document.getElementById('input-prix');
    const liveIndicator = document.getElementById('live-prix-litre');

    function updateCalculPrixLitre() {
        if (!inputLitres || !inputPrix || !liveIndicator) return;
        const litres = parseFloat(inputLitres.value);
        const prix = parseFloat(inputPrix.value);

        if (!isNaN(litres) && !isNaN(prix) && litres > 0 && prix > 0) {
            const ratio = (prix / litres).toFixed(3);
            liveIndicator.textContent = ratio;
        } else {
            liveIndicator.textContent = '-';
        }
    }

    if (inputLitres && inputPrix) {
        inputLitres.addEventListener('input', updateCalculPrixLitre);
        inputPrix.addEventListener('input', updateCalculPrixLitre);
    }
});