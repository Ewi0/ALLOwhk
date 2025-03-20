// Cart logic 
// Функция для отображения всплывающего сообщения
function showPopupMessage(message, type = 'success') {
    const popup = $('#popup-message');
    popup.removeClass('alert-success alert-danger').addClass('alert-' + type).text(message).fadeIn();

    // Исчезает через 3 секунды
    setTimeout(function() {
        popup.fadeOut();
    }, 3000);
}

// Функция для загрузки содержимого корзины
function loadCart() {
    $.ajax({
        url: 'backend_cart.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#cart-items').html(response.html);
            $('#cart-total').text(response.total);
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ', error);
        }
    });
}

// Загрузка корзины при загрузке страницы
$(document).ready(function() {
    loadCart();

    // Добавление товара в корзину
    $(document).on('click', '.add-to-cart', function() {
        var partId = $(this).data('id');
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'add', part_id: partId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadCart();
                    showPopupMessage('Товар добавлен в корзину!');
                } else {
                    showPopupMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
                showPopupMessage('Ошибка при добавлении товара', 'danger');
            }
        });
    });

    // Обновление количества товара в корзине
$(document).on('change', '.cart-quantity', function() {
    var partId = $(this).data('id');
    var quantity = $(this).val();
    $.ajax({
        url: 'backend_cart.php',
        method: 'POST',
        data: { action: 'update', part_id: partId, quantity: quantity },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                loadCart();
                showPopupMessage(response.message);
            } else {
                showPopupMessage(response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ошибка AJAX: ', error);
            showPopupMessage('Ошибка при обновлении товара', 'danger');
        }
    });
});


    // Удаление товара из корзины
    $(document).on('click', '.remove-from-cart', function() {
        var partId = $(this).data('id');
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'remove', part_id: partId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadCart();
                    showPopupMessage('Товар удален из корзины!');
                } else {
                    showPopupMessage(response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
                showPopupMessage('Ошибка при удалении товара', 'danger');
            }
        });
    });

    // Оформление заказа (checkout)
    $('#checkout-btn').on('click', function() {
        $.ajax({
            url: 'backend_cart.php',
            method: 'POST',
            data: { action: 'checkout' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert('Заказ успешно оформлен!');
                    loadCart();  // Очищаем корзину
                } else {
                    alert('Ошибка: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ошибка AJAX: ', error);
            }
        });
    });
});
// END for cart logic

// Search FORM focusing logic
$(document).ready(function() {
    // Фокусируемся на поле поиска при загрузке страницы
    $("#search").focus();

    // Обработка клика на "Добавить в корзину"
    $(this).on("click", function() {
        // Используем setTimeout, чтобы не сразу возвращать фокус
        setTimeout(function() {
            // Проверяем, не фокусируется ли пользователь на другом элементе
            if (!$(":focus").is("input, textarea, select")) {
                $("#search").focus();
            }
        }, 200);
    });
});
// ARTICLE copying system
$(document).ready(function(){
    $('.copyData').click(function(){
      var $this = $(this);
      var originalText = $this.text().trim();
      var textToCopy = originalText;

      // Copy the text to the clipboard using the fallback method
      var $tempInput = $('<input>');
      $('body').append($tempInput);
      $tempInput.val(textToCopy).select();
      document.execCommand('copy');
      $tempInput.remove();

      // Change the text to indicate copying
      $this.text('Copied!');
      
      // Change the text back to the original after 1 seconds
      setTimeout(function(){
        $this.text(originalText);
      }, 1000); // 1000 milliseconds = 1 seconds
    });
  });
// Search suggestor

$(document).on('click', '.suggestion-item', function () {
    // Найдём ближайшую форму и отправим её
    $('#search').closest('form').submit();
});

document.getElementById('search').addEventListener('input', function() {
    let query = this.value;

    if (query.length >= 2) {  // Trigger suggestions after 2 characters
        fetch('search_suggestions.php?query=' + query)
            .then(response => response.json())
            .then(data => {
                let suggestionBox = document.getElementById('suggestionBox');
                suggestionBox.innerHTML = '';  // Clear previous suggestions
                
                data.forEach(item => {
                    let suggestionDiv = document.createElement('div');
                    suggestionDiv.classList.add('suggestion-item');  // Add class for styling
                    
                    // Structure the suggestion box to show part name, article, description, and barcode
                    suggestionDiv.innerHTML = `
                        <strong>${item.part_name}</strong> <br>
                        <small>Article: ${item.article}</small> <br>
                        <small>Description: ${item.description}</small> <br>
                        <small>Barcode: ${item.barcode}</small>
                    `;
                    suggestionDiv.addEventListener('click', function() {
                        document.getElementById('search').value = item.part_name;  // Set part name in the input field
                        suggestionBox.innerHTML = '';  // Clear suggestions after selection
                    });
                    suggestionBox.appendChild(suggestionDiv);
                });
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    } else {
        document.getElementById('suggestionBox').innerHTML = '';  // Clear suggestions if fewer than 2 characters are typed
    }
});
//Barcode system
// Function to detect barcode type based on the barcode input
function detectBarcodeType(barcode) {
    if (/^\\d{12}$/.test(barcode)) {
        return 'UPC';  // UPC-A (12 digits)
    } else if (/^\\d{13}$/.test(barcode)) {
        return 'EAN13';  // EAN-13 (13 digits)
    } else {
        return 'CODE128';  // Default to Code128 for alphanumeric or unknown formats
    }
}

// Function to generate the barcode and fit it to a 56mm width label
function generateBarcodeSVG(barcode) {
    const barcodeType = detectBarcodeType(barcode);

    // Generate the barcode directly into the SVG element using JsBarcode
    let svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    JsBarcode(svg, barcode, {
        format: barcodeType,  // Detected barcode type
        displayValue: true,   // Display the text below the barcode
        fontSize: 16,         // Font size for the text
        width: 1.1,  // Width of each bar, calculated to fit the 56mm label
        height: 10           // Height of the bars (can be adjusted if needed)
    });

    return svg.outerHTML;
}

// Function to print individual labels with padding above the article
function printLabel(partId) {
    const row = document.querySelector(`tr[data-id='${partId}']`);
    if (!row) return;

    const article = row.cells[0].textContent.trim();
    const partName = row.cells[1].textContent.trim();
    const price = row.cells[3].textContent.trim();
    const barcode = row.getAttribute('data-barcode') || '';
    const barcodeSVG = generateBarcodeSVG(barcode); // уже у тебя реализован

    const labelWindow = window.open('', '_blank', 'width=1280,height=720');

    labelWindow.document.write(`
        <html>
        <head><title>Label</title></head>
        <body onload="window.print(); window.onafterprint = window.close();" style="font-family: Arial, sans-serif; text-align: center;">
            <div style="padding: 3px;">
                <div style="font-size: 15px; margin-top: 1px; font-weight: bold;">${article}</div>
                <div style="font-size: 13px;">${partName}</div>
                <div style="font-size: 20px; margin-top: 1px; font-weight: bold;">${price}</div>
                <div style="margin-top: 2px;">${barcodeSVG}</div>
            </div>
        </body>
        </html>
    `);

    labelWindow.document.close();
}
