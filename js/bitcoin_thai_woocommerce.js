jQuery(function($) {
  var text;
  var pass = false;
  var form = $("#mainform");
  var form_field = $("#woocommerce_coinpay_cryptocurrencies");
  if( form_field.get(0) ) {
    form.on('submit',function(e) {
      if(form_field.val().length > 0) {
        e.defaultPrevented;
        // RegEx by comma, period, white space
        ticker_array = form_field.val().split(/[., ]+/);
        for(var i = 0; i < ticker_array.length; i++)
        {
          // Trim the data item
          let data = ticker_array[i].trim();
          if( data.length <= 2 || data.length > 5 || data.match(/^[a-zA-Z0-9]*$/) === null) {
            pass = false;
            break;
          }else{
            pass = true;
          }
        } // end for loop
      }else{
        pass = true;
      }

      if( pass == true ) {
        text = "Example: BTC, BCH, DAS, DOG, LTC";
        form_field.next().html(text);
        $(this).off('submit').submit();
      }else {
        text = "<span style='color:red'>Input not valid. Example: BTC, BCH, DAS, DOG, LTC</span>";
        form_field.next().html(text);
      }
    });
  }
});
