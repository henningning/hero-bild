(function($){
  $(function(){
    var frame;
    $(document).on('click','#hr-hero-default-choose',function(e){
      e.preventDefault();
      if(!frame){
        frame=wp.media({title:'Symbolbild auswählen',button:{text:'Als Symbolbild verwenden'},library:{type:'image'},multiple:false});
        frame.on('select',function(){
          var attachment=frame.state().get('selection').first().toJSON();
          $('#hr-hero-default-id').val(attachment.id).trigger('change');
          var url=(attachment.sizes&&attachment.sizes.medium?attachment.sizes.medium.url:attachment.url);
          $('#hr-hero-default-preview').empty().append($('<img>',{src:url,alt:''}).css({maxWidth:'100%',height:'auto',display:'block',marginBottom:'8px'}));
          $('#hr-hero-default-remove').show();
        });
      }
      frame.open();
    });
    $(document).on('click','#hr-hero-default-remove',function(e){
      e.preventDefault(); $('#hr-hero-default-id').val(''); $('#hr-hero-default-preview').empty(); $(this).hide();
    });
  });
})(jQuery);
