(function($){
  'use strict';
  $(document).on('click', '.hr-hero-term-choose', function(e) {
    e.preventDefault();
    var picker = $(this).closest('.hr-hero-term-picker');
    var frame = wp.media({
      title: 'Hero-Bild für diese Kategorie auswählen',
      button: {text: 'Als Hero-Bild verwenden'},
      library: {type: 'image'}, multiple: false
    });
    frame.on('select', function() {
      var attachment = frame.state().get('selection').first().toJSON();
      var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
      picker.find('.hr-hero-term-image-id').val(attachment.id);
      picker.find('.hr-hero-term-preview').empty().append($('<img>',{src:url,alt:''}).css({maxWidth:'100%',height:'auto',display:'block',marginBottom:'8px'}));
      picker.find('.hr-hero-term-remove').show();
    });
    frame.open();
  });
  $(document).on('click', '.hr-hero-term-remove', function(e) {
    e.preventDefault();
    var picker = $(this).closest('.hr-hero-term-picker');
    picker.find('.hr-hero-term-image-id').val('');
    picker.find('.hr-hero-term-preview').empty();
    $(this).hide();
  });
  // Die Hinzufügen-Maske arbeitet bei Kategorien per AJAX und wird danach geleert.
  $(document).ajaxSuccess(function(event, xhr, settings) {
    if (typeof settings.data === 'string' && /(?:^|&)action=add-tag(?:&|$)/.test(settings.data)) {
      $('.hr-hero-term-picker[data-context="add"]').each(function(){
        var picker=$(this);
        picker.find('.hr-hero-term-image-id').val('');
        picker.find('.hr-hero-term-preview').empty();
        picker.find('.hr-hero-term-remove').hide();
      });
    }
  });
})(jQuery);
