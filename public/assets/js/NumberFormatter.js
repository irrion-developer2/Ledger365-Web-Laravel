function jsIndianFormat(amount) {
    amount = parseFloat(amount).toFixed(2);
    
    var isNegative = amount.startsWith('-');
    
    var parts = amount.split('.');
    var integerPart = isNegative ? parts[0].slice(1) : parts[0];
    var decimalPart = parts.length > 1 ? '.' + parts[1] : '';
    
    var lastThreeDigits = integerPart.slice(-3);
    var otherDigits = integerPart.slice(0, -3);
    
    if (otherDigits !== '') {
        lastThreeDigits = ',' + lastThreeDigits;
    }
    
    var indianFormatted = otherDigits.replace(/\B(?=(\d{2})+(?!\d))/g, ',') + lastThreeDigits + decimalPart;

    if (isNegative) {
        indianFormatted = '-' + indianFormatted;
    }
    
    return indianFormatted;
}