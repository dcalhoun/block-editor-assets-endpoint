(function (blocks, element) {
  var el = element.createElement;

  blocks.registerBlockType("custom-block/custom-block", {
    title: "Custom Block",
    icon: "smiley",
    category: "common",
    edit: function (props) {
      return el(wp.blockEditor.RichText, {
        tagName: "p",
        className: `custom-block ${props.className}`,
        value: props.attributes.content,
        onChange: function (newContent) {
          props.setAttributes({ content: newContent });
        },
      });
    },
    save: function () {
      return el("p", {}, props.attributes.content);
    },
  });
})(window.wp.blocks, window.wp.element);
