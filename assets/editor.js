(function(wp){
  'use strict';
  var el=wp.element.createElement;
  var __=wp.i18n.__;
  var InspectorControls=wp.blockEditor.InspectorControls;
  var PanelBody=wp.components.PanelBody;
  var SelectControl=wp.components.SelectControl;
  var RangeControl=wp.components.RangeControl;
  var Notice=wp.components.Notice;
  var ToggleControl=wp.components.ToggleControl;
  var TextControl=wp.components.TextControl;
  var ColorPalette=wp.components.ColorPalette;
  var ServerSideRender=wp.serverSideRender;
  function range(label,key,min,max,step,props){
    return el(RangeControl,{label:label,value:props.attributes[key],onChange:function(n){var a={};a[key]=n;props.setAttributes(a);},min:min,max:max,step:step||1});
  }
  function select(label,key,options,props){
    return el(SelectControl,{label:label,value:props.attributes[key],options:options,onChange:function(v){var a={};a[key]=v;props.setAttributes(a);}});
  }
  function color(label,key,props){
    return el('div',{style:{marginBottom:'14px'}},
      el('label',{style:{display:'block',marginBottom:'6px'}},label),
      el(ColorPalette,{value:props.attributes[key],clearable:false,onChange:function(v){var a={};a[key]=v||'#ffffff';props.setAttributes(a);}})
    );
  }
  var globalData=window.hrHeroEditorData || {global:{},settingsUrl:''};
  wp.blocks.registerBlockType('hr/hero-bild',{
    edit:function(props){
      var useGlobal=props.attributes.useGlobalSettings!==false;
      var widthMode=useGlobal ? (globalData.global.widthMode || 'full') : props.attributes.widthMode;
      return el(wp.element.Fragment,null,
        el(InspectorControls,null,
          el(PanelBody,{title:'Globale Darstellung',initialOpen:true},
            el(ToggleControl,{label:'Globale Einstellungen verwenden',checked:useGlobal,onChange:function(v){props.setAttributes({useGlobalSettings:v});}}),
            el('p',{className:'components-base-control__help'},useGlobal ? 'Breite, Höhen, Effekte und Bildinformationen werden zentral unter „Hero-Bild“ im WordPress-Menü verwaltet.' : 'Die bisherigen Einstellungen dieses Blocks gelten unabhängig von den globalen Vorgaben.'),
            el('a',{href:globalData.settingsUrl,target:'_blank',rel:'noopener noreferrer'},'Globale Einstellungen öffnen ↗')
          ),
          el(PanelBody,{title:__('Bildquelle','hero-bild'),initialOpen:true},
            select('Bild verwenden aus','imageSource',[
              {label:'Automatisch (Seite, Beitrag oder Kategorie)',value:'auto'},
              {label:'Aktuelle Kategorie / Taxonomie',value:'term'},
              {label:'Aktueller Beitrag / Seite',value:'post'}
            ],props),
            el('p',{className:'components-base-control__help'},
              'Für Kategorie-Templates „Aktuelle Kategorie / Taxonomie“ wählen. Der Block gehört außerhalb der Beitragsliste (Abfrage-Loop). Für Beiträge und Seiten gilt immer: Hero-Bild → Beitragsbild → globales Symbolbild.'
            )
          ),
          !useGlobal ? el(PanelBody,{title:__('Bildgröße','hero-bild'),initialOpen:true},
            select('Breite','widthMode',[
              {label:'Volle verfügbare Breite',value:'full'},
              {label:'Weite Inhaltsbreite',value:'wide'},
              {label:'Normale Inhaltsbreite',value:'content'},
              {label:'Eigene maximale Breite',value:'custom'}
            ],props),
            props.attributes.widthMode==='custom'?range('Maximale Breite (px)','maxWidth',200,3000,10,props):null,
            range('Höhe Desktop (px)','heightDesktop',60,1200,10,props),
            range('Höhe Tablet bis 1024 px','heightTablet',60,1000,10,props),
            range('Höhe Mobil bis 600 px','heightMobile',40,800,10,props),
            select('Bildausschnitt','objectPosition',[
              {label:'Oben',value:'top'},{label:'Mitte',value:'center'},{label:'Unten',value:'bottom'}
            ],props),
            range('Eckenradius (px)','radius',0,100,1,props)
          ) : null,
          !useGlobal ? el(PanelBody,{title:__('Hover-Effekt','hero-bild'),initialOpen:true},
            select('Effekt','hoverEffect',[
              {label:'Keiner',value:'none'}, {label:'Sanfter Zoom',value:'zoom'},
              {label:'Abdunkeln',value:'dim'}, {label:'Aufhellen',value:'bright'}
            ],props),
            props.attributes.hoverEffect==='zoom'?range('Zoom (%)','zoomPercent',0,20,1,props):null,
            props.attributes.hoverEffect!=='none'?range('Animationsdauer (ms)','duration',0,2000,50,props):null
          ) : null,
          !useGlobal ? el(PanelBody,{title:__('Bildinformationen','hero-bild'),initialOpen:false},
            el(ToggleControl,{label:'Infokasten anzeigen',checked:props.attributes.showImageInfo,onChange:function(v){props.setAttributes({showImageInfo:v});}}),
            props.attributes.showImageInfo ? el(wp.element.Fragment,null,
              el(TextControl,{label:'Beschriftung',value:props.attributes.infoLabel,onChange:function(v){props.setAttributes({infoLabel:v});}}),
              select('Position','infoPosition',[
                {label:'Unten rechts',value:'bottom-right'}, {label:'Unten links',value:'bottom-left'},
                {label:'Oben rechts',value:'top-right'}, {label:'Oben links',value:'top-left'}
              ],props),
              range('Schriftgröße (px)','infoFontSize',10,26,1,props),
              range('Innenabstand (px)','infoPadding',4,32,1,props),
              range('Randabstand (px)','infoOffset',0,80,1,props),
              range('Eckenradius (px)','infoRadius',0,40,1,props),
              color('Hintergrund: Schaltfläche','infoBackground',props),
              color('Text: Schaltfläche','infoTextColor',props),
              color('Hintergrund: Informationsfeld','infoPanelBackground',props),
              color('Text: Informationsfeld','infoPanelTextColor',props)
            ):null
          ) : null
        ),
        el('div' ,wp.blockEditor.useBlockProps({className:widthMode==='full'?'alignfull':(widthMode==='wide'?'alignwide':'')}),
          el(ServerSideRender,{block:'hr/hero-bild',attributes:props.attributes,
            EmptyResponsePlaceholder:function(){return el(Notice,{status:'info',isDismissible:false},'Im Site-Editor ist keine konkrete Kategorie ausgewählt. Auf der öffentlichen Kategorieseite erscheint ihr zugeordnetes Hero-Bild. Fehlt ein Bild, wird das globale Symbolbild verwendet, sofern eines festgelegt ist.');}
          })
        )
      );
    },
    save:function(){return null;}
  });
})(window.wp);
