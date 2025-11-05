(function(wp) {
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;
    var PanelColorSettings = wp.blockEditor ? wp.blockEditor.PanelColorSettings : (wp.editor.PanelColorSettings || null);
    var SelectControl = wp.components.SelectControl;
    var PanelBody = wp.components.PanelBody;
    var Placeholder = wp.components.Placeholder;
    var ToggleControl = wp.components.ToggleControl;
    var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;
    var useState = wp.element.useState;
    
    registerBlockType('keylouds/cloud', {
        title: 'Keyword Cloud',
        icon: 'tag',
        category: 'widgets',
        attributes: {
            cloudId: {
                type: 'number',
                default: 0
            },
            colorSmall: {
                type: 'string',
                default: ''
            },
            colorMedium: {
                type: 'string',
                default: ''
            },
            colorLarge: {
                type: 'string',
                default: ''
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var cloudId = attributes.cloudId;
            var colorSmall = attributes.colorSmall;
            var colorMedium = attributes.colorMedium;
            var colorLarge = attributes.colorLarge;
            
            // Get available clouds from localized data
            var clouds = (typeof keyloudsBlock !== 'undefined' && keyloudsBlock.clouds) ? keyloudsBlock.clouds : [];
            
            // Create options for select
            var options = [
                { label: 'Select a Keyword Cloud', value: 0 }
            ];
            
            clouds.forEach(function(cloud) {
                options.push({
                    label: cloud.title,
                    value: parseInt(cloud.id)
                });
            });
            
            function onChangeCloud(newCloudId) {
                setAttributes({ cloudId: parseInt(newCloudId) });
            }
            
            // Color change handlers
            function onChangeColorSmall(newColor) {
                setAttributes({ colorSmall: newColor || '' });
            }
            
            function onChangeColorMedium(newColor) {
                setAttributes({ colorMedium: newColor || '' });
            }
            
            function onChangeColorLarge(newColor) {
                setAttributes({ colorLarge: newColor || '' });
            }
            
            // Build inspector controls
            var inspectorPanels = [
                el(
                    PanelBody,
                    { title: 'Cloud Settings', initialOpen: true },
                    el(SelectControl, {
                        label: 'Select Keyword Cloud',
                        value: cloudId,
                        options: options,
                        onChange: onChangeCloud
                    })
                )
            ];
            
            // Add color settings if PanelColorSettings is available
            if (PanelColorSettings) {
                inspectorPanels.push(
                    el(PanelColorSettings, {
                        title: 'Word Colors',
                        initialOpen: false,
                        colorSettings: [
                            {
                                value: colorSmall,
                                onChange: onChangeColorSmall,
                                label: 'Small Words (< 1em)'
                            },
                            {
                                value: colorMedium,
                                onChange: onChangeColorMedium,
                                label: 'Medium Words (1-1.5em)'
                            },
                            {
                                value: colorLarge,
                                onChange: onChangeColorLarge,
                                label: 'Large Words (≥ 1.5em)'
                            }
                        ]
                    })
                );
            } else {
                // Fallback for older WordPress versions
                inspectorPanels.push(
                    el(
                        PanelBody,
                        { title: 'Word Colors', initialOpen: false },
                        el('p', { style: { fontSize: '12px', fontStyle: 'italic' } }, 
                            'Color controls require a newer WordPress version. Use shortcode attributes instead.'
                        )
                    )
                );
            }
            
            var inspectorControls = el(
                InspectorControls,
                {},
                inspectorPanels
            );
            
            // If no cloud selected, show placeholder
            if (!cloudId || cloudId === 0) {
                return el(
                    Fragment,
                    {},
                    inspectorControls,
                    el(
                        Placeholder,
                        {
                            icon: 'tag',
                            label: 'Keyword Cloud',
                            instructions: 'Select a keyword cloud from the sidebar settings to display it.'
                        },
                        clouds.length === 0 
                            ? el('p', {}, 'No keyword clouds available. Create one in the Keylouds admin page.')
                            : null
                    )
                );
            }
            
            // Show server-side render of the cloud
            if (ServerSideRender) {
                return el(
                    Fragment,
                    {},
                    inspectorControls,
                    el(ServerSideRender, {
                        block: 'keylouds/cloud',
                        attributes: attributes
                    })
                );
            } else {
                // Fallback if ServerSideRender is not available
                return el(
                    Fragment,
                    {},
                    inspectorControls,
                    el(
                        Placeholder,
                        {
                            icon: 'tag',
                            label: 'Keyword Cloud Preview'
                        },
                        el('p', {}, 'Cloud ID: ' + cloudId + ' (preview on frontend)')
                    )
                );
            }
        },
        
        save: function() {
            // Server-side rendering
            return null;
        }
    });
    
})(window.wp);

