(function($){
  $(function(){
    var frame;
    $(document).on('click','#hr-hero-choose',function(e){
      e.preventDefault();
      if(!frame){
        frame=wp.media({title:'Hero-Bild auswählen',button:{text:'Als Hero-Bild verwenden'},library:{type:'image'},multiple:false});
        frame.on('select',function(){
          var attachment=frame.state().get('selection').first().toJSON();
          $('#hr-hero-image-id').val(attachment.id).trigger('change');
          var url=(attachment.sizes&&attachment.sizes.medium?attachment.sizes.medium.url:attachment.url);
          $('#hr-hero-meta-preview').empty().append($('<img>',{src:url,alt:''}).css({maxWidth:'100%',height:'auto',display:'block',marginBottom:'8px'}));
          $('#hr-hero-remove').show();
        });
      }
      frame.open();
    });
    $(document).on('click','#hr-hero-remove',function(e){
      e.preventDefault(); $('#hr-hero-image-id').val(''); $('#hr-hero-meta-preview').empty(); $(this).hide();
    });
  });
})(jQuery);
