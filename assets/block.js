(function (blocks, element, blockEditor, components, i18n, apiFetch) {
  var el = element.createElement;
  var Fragment = element.Fragment;
  var InspectorControls = blockEditor.InspectorControls;
  var PanelBody = components.PanelBody;
  var SelectControl = components.SelectControl;
  var TextControl = components.TextControl;
  var RangeControl = components.RangeControl;
  var __ = i18n.__;

  var TYPE_OPTIONS = [
    { label: __("Donate page", "one-ngo-fundraising"), value: "donate" },
    { label: __("Campaigns", "one-ngo-fundraising"), value: "campaigns" },
    { label: __("Single campaign", "one-ngo-fundraising"), value: "campaign" },
    { label: __("Events", "one-ngo-fundraising"), value: "events" },
    { label: __("Single event", "one-ngo-fundraising"), value: "event" },
    { label: __("Stories", "one-ngo-fundraising"), value: "stories" },
    { label: __("Single story", "one-ngo-fundraising"), value: "story" },
  ];

  function needsSlug(type) {
    return type === "campaign" || type === "event" || type === "story";
  }

  function supportsLimit(type) {
    return type === "campaigns" || type === "events" || type === "stories";
  }

  blocks.registerBlockType("one-ngo/embed", {
    edit: function (props) {
      var type = props.attributes.type || "donate";
      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __("1 NGO embed", "one-ngo-fundraising"), initialOpen: true },
            el(SelectControl, {
              label: __("Type", "one-ngo-fundraising"),
              value: type,
              options: TYPE_OPTIONS,
              onChange: function (value) {
                props.setAttributes({ type: value });
              },
            }),
            needsSlug(type)
              ? el(TextControl, {
                  label: __("Slug", "one-ngo-fundraising"),
                  value: props.attributes.slug || "",
                  onChange: function (value) {
                    props.setAttributes({ slug: value });
                  },
                })
              : null,
            supportsLimit(type)
              ? el(RangeControl, {
                  label: __("Limit", "one-ngo-fundraising"),
                  value: props.attributes.limit || 3,
                  min: 1,
                  max: 12,
                  onChange: function (value) {
                    props.setAttributes({ limit: value });
                  },
                })
              : null
          )
        ),
        el(
          "div",
          { className: "components-placeholder", style: { minHeight: "120px" } },
          el("p", null, __("1 NGO — saved output is served on this WordPress site. Connect the plugin under Settings → 1 NGO.", "one-ngo-fundraising")),
          el("code", null, '[1ngo type="' + type + '"]')
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
  window.wp.apiFetch
);
