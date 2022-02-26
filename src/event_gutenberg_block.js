var __ = wp.i18n.__;


var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender = wp.serverSideRender,
    TextControl = wp.components.TextControl,
	PanelBody = wp.components.PanelBody,
	PanelRow = wp.components.PanelRow,
	RangeControl = wp.components.RangeControl,
    TextareaControl = wp.components.TextareaControl,
    ToggleControl = wp.components.ToggleControl,
    InspectorControls = wp.blockEditor.InspectorControls;


registerBlockType( 'evkirchentermin/small-event-list', {
  title: __('small event list', 'ev-kirchen-termine'),
  icon: "calendar",
  category: 'widgets',

  attributes: {

    'channel': {
      type: 'string',
      default: ""
    },
    'limit': {
      type: 'integer',
      default: 5
    },

    'event_ids': {
      type: 'string',
      default: ""
    },

    'show_location': {
      type: 'boolean',
      default: true
    },

	'vid': {
      type: 'string',
      default: ""
    },

  },


  edit: (props) => {

    if(props.isSelected){
      //console.debug(props.attributes);
    };

    return [
      /**
       * Server side render
       */
      el("div", {
            className: "mb-editor-container",
            style: {}
          },
          el( ServerSideRender, {
            block: 'evkirchentermin/small-event-list',
            attributes: props.attributes
          } )
      ),

      /**
       * Inspector
       */
      el( InspectorControls,
          {}, [

			el( PanelBody, { title: __('Settings', 'ev-kirchen-termine'), initialOpen: true },

				el( PanelRow, {},

					el( TextControl, {
					  label: __('Channel Filter', 'ev-kirchen-termine'),
					  value: props.attributes.channel,
					  onChange: ( value ) => {
						props.setAttributes( { channel: value } );
					  }
					} ),

				),

				el( PanelRow, {},

					el( RangeControl, {
					  label: __('Count', 'ev-kirchen-termine'),
					  min: 1,
					  max: 20,
					  value: props.attributes.limit,
					  onChange: ( value ) => {
						props.setAttributes( { limit: value } );
					  }
					} ),

				),

				el( PanelRow, {},

					el( TextControl, {
					  label: __('Include Event-IDs', 'ev-kirchen-termine'),
					  value: props.attributes.event_ids,
					  onChange: ( value ) => {
						props.setAttributes( { event_ids: value } );
					  }
					} ),

				),

                el( PanelRow, {},

					el( ToggleControl, {
					  label: __('Show location', 'ev-kirchen-termine'),
					  checked: props.attributes.show_location,
					  onChange: ( value ) => {
						props.setAttributes( { show_location: value } );
					  }
					} ),

				),

				el( PanelRow, {},

					el( TextControl, {
					  label: __('VID Filter', 'ev-kirchen-termine'),
					  value: props.attributes.vid,
					  onChange: ( value ) => {
						props.setAttributes( { vid: value } );
					  }
					} ),

				),

			),

          ]
      )
    ]
  },

  save: () => {
    /** this is resolved server side */
    return null
  }
} );
