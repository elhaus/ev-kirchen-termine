

var el = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender = wp.components.ServerSideRender,
    TextControl = wp.components.TextControl,
	PanelBody = wp.components.PanelBody,
	PanelRow = wp.components.PanelRow,
	RangeControl = wp.components.RangeControl,
    TextareaControl = wp.components.TextareaControl,
    InspectorControls = wp.editor.InspectorControls;


registerBlockType( 'evkirchentermin/small-event-list', {
  title: 'small event list',
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

			el( PanelBody, { title: 'Settings', initialOpen: true },

				el( PanelRow, {},

					el( TextControl, {
					  label: 'Channel Filter',
					  value: props.attributes.channel,
					  onChange: ( value ) => {
						props.setAttributes( { channel: value } );
					  }
					} ),

				),

				el( PanelRow, {},

					el( RangeControl, {
					  label: 'Count',
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
					  label: 'Include Event-IDs',
					  value: props.attributes.event_ids,
					  onChange: ( value ) => {
						props.setAttributes( { event_ids: value } );
					  }
					} ),

				),

				el( PanelRow, {},

					el( TextControl, {
					  label: 'VID Filter',
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
