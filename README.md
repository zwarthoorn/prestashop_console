# prestashop_console
PrestaShop Console App

## Copy missing translation from source in Prestashop Admin
```javascript
$('#translations_form input, #translations_form textarea').not(':hidden').each(function() {
    if($(this).val() == '') {
        $siblings = $(this).parent().parent().find('td:first-of-type');
        $(this).val('XXXXXXXXXX ' + $siblings[0].innerText);
    }
});
```
