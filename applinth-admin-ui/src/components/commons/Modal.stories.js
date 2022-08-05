import Modal from "./Modal.vue";

/**
 * Issue viewing this component in Storybook
 * https://github.com/shentao/vue-multiselect/issues/966
 */

export default {
  title: "Common components/Modal",
  component: Modal,
};

const Template = (args, { argTypes }) => ({
  props: Object.keys(argTypes),
  components: { Modal },
  template: '<Modal v-bind="$props" />',
});

export const Default = Template.bind({});
Default.args = {
  value: true,
  cancelBtnText: "Cancel",
  confirmBtnText: "Confirm",
  title: "Modal title",
  body: "Modal body",
};

export const IsLoading = Template.bind({});
IsLoading.args = {
  ...Default.args,
  isLoading: true,
};

export const IsSending = Template.bind({});
IsSending.args = {
  ...Default.args,
  isSending: true,
};

export const MaxWidth1000 = Template.bind({});
MaxWidth1000.args = {
  ...Default.args,
  maxWidth: 1000,
};

export const LongContent = Template.bind({});
LongContent.args = {
  ...Default.args,
  body: `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce dictum ultricies risus id mollis. Aliquam eget urna egestas, vulputate quam sed, elementum eros. Morbi pretium ipsum sit amet dictum feugiat. Proin congue quam ut sem iaculis, sit amet maximus ligula lacinia. Sed non lorem et odio ornare auctor. Sed blandit felis libero, ac interdum neque sagittis non. Aenean vel dui eros.

  Praesent ac laoreet purus. Cras aliquet efficitur consequat. Phasellus vestibulum, urna quis tristique lacinia, nisi sem consectetur lacus, a mollis lorem ligula porta dui. Quisque volutpat ligula eros, in pulvinar dui volutpat et. Suspendisse dictum mauris eget molestie condimentum. Ut lobortis, metus vel volutpat scelerisque, erat nunc scelerisque nibh, sed fringilla magna odio sed risus. Vestibulum eu congue lacus. Etiam hendrerit nisi non risus scelerisque, a blandit orci pretium. Duis egestas risus ac leo vestibulum volutpat. In venenatis non diam a venenatis. Suspendisse lobortis mattis leo nec consequat.
  
  Integer pretium non velit nec volutpat. Donec nisl odio, porta sed consequat vitae, porttitor eget erat. Ut non nisl sit amet sapien ultricies semper. Praesent non purus urna. Etiam scelerisque nulla quis dui porta scelerisque. Praesent convallis tortor et lorem mattis, vel tincidunt urna sagittis. Ut vehicula nunc ac ante aliquam, quis eleifend dolor placerat. Curabitur viverra dolor nec sem facilisis ullamcorper. Duis eu accumsan mi. Fusce sit amet blandit metus. Fusce lacinia sapien eu consequat varius.
  
  Maecenas velit diam, faucibus at feugiat euismod, cursus quis velit. Duis tempor ex tellus, in laoreet purus bibendum ullamcorper. Aenean sollicitudin, lectus a convallis posuere, ex sapien tincidunt enim, at scelerisque nibh odio eget felis. Mauris fringilla, arcu id elementum interdum, ipsum eros semper ipsum, eu ornare libero neque ac arcu. Duis in condimentum ipsum. Etiam semper suscipit hendrerit. Quisque ac condimentum eros, vel pellentesque tortor.
  
  Pellentesque nec tincidunt odio, laoreet posuere tellus. Morbi sagittis sit amet dolor quis iaculis. Nullam a ex neque. Ut eu ligula ullamcorper turpis cursus aliquet. Curabitur sed fermentum nunc. Aliquam arcu augue, elementum sed lacus eget, fermentum gravida ante. Morbi accumsan neque sem, a sagittis justo venenatis eget. Cras sed nulla faucibus, fermentum libero eget, bibendum nisi. Donec dictum tellus eu est congue condimentum quis vitae neque. Nunc posuere ante id purus fermentum, at interdum lacus iaculis. Phasellus aliquam ut velit ac sollicitudin. Phasellus placerat risus eget leo posuere faucibus. Sed et diam felis. Aenean vitae mi eu quam tempor blandit. Etiam libero mi, euismod ut tincidunt a, tincidunt at elit. Fusce luctus sollicitudin urna ac laoreet.
  
  Curabitur ultrices lectus in erat bibendum, nec interdum felis imperdiet. Integer scelerisque vestibulum velit, et commodo leo convallis sed. Duis sit amet consequat est. Suspendisse ultrices metus ante, ut condimentum nunc luctus vel. Nunc id iaculis tortor, vel tempus nisl. Morbi porttitor, orci nec ultricies volutpat, tortor purus tempus ipsum, et semper nibh enim ac nunc. Duis id risus sapien. Aenean porttitor mi vel arcu accumsan rhoncus. Sed purus felis, rhoncus et dapibus eu, tincidunt quis urna. Aliquam eleifend pharetra sapien non imperdiet.
  
  Duis venenatis nisl orci, ut finibus tellus tincidunt non. Ut a ligula nec orci sagittis sollicitudin id sit amet odio. Nam tempus, quam at mollis bibendum, sem nisi accumsan eros, non imperdiet magna nulla ut risus. Nunc a felis enim. Vestibulum at ultrices magna. Nunc a finibus felis. Quisque iaculis tristique ante, ut volutpat odio tempor vitae. Phasellus aliquam volutpat convallis. Vestibulum porta, est nec tincidunt condimentum, metus orci porta mauris, at dapibus nunc est quis ligula.
  
  Praesent metus turpis, gravida non tincidunt eget, iaculis in felis. Maecenas tristique leo non leo laoreet rhoncus. In dapibus, massa et sollicitudin ultricies, tellus orci tincidunt odio, venenatis iaculis enim ipsum eget purus. Nulla facilisi. Donec lorem erat, lacinia bibendum tincidunt sed, aliquet sit amet neque. Ut eu urna metus. In euismod malesuada erat, et fringilla nunc ultrices quis. Fusce luctus eros et sapien mollis accumsan. Curabitur vitae mi ac nisl pellentesque tristique blandit non lectus. Quisque et tortor quis risus venenatis elementum vel vitae sapien. Aliquam euismod justo ultrices sapien laoreet, vestibulum malesuada nulla venenatis.
  
  Donec ac volutpat lacus, vitae dictum nisi. Duis egestas turpis odio, in egestas nibh lacinia sit amet. Praesent dolor ligula, vulputate et dolor vitae, gravida imperdiet neque. Etiam nisi purus, volutpat vel feugiat tincidunt, convallis vel libero. Nunc at cursus neque, non aliquam massa. Nulla facilisi. Integer varius nec urna a elementum. Sed quam tellus, egestas ac dui at, varius eleifend magna. Pellentesque quis sagittis tellus.
  
  Donec efficitur iaculis mauris, eu pellentesque odio sagittis at. Donec lectus sapien, interdum id ante ut, pretium pulvinar neque. Cras aliquam est quis lectus suscipit, ac egestas libero porttitor. Donec eget ultrices sem, id rhoncus dui. Donec sed sem ut mauris dictum semper. Nunc pretium, justo et mattis volutpat, velit felis malesuada odio, eget laoreet erat purus in sapien. Praesent nisi erat, rutrum ut accumsan sed, finibus sit amet quam. Vivamus at dapibus quam.
  
  Integer mattis ante pharetra, dapibus arcu in, pellentesque magna. Curabitur sed ipsum vitae est rutrum lobortis at non metus. Proin mattis semper nibh eget porta. Suspendisse sit amet blandit mi. Morbi vel finibus tellus, ac euismod nulla. Nam dignissim lacus eu consectetur ultricies. Vestibulum velit enim, varius ac imperdiet vitae, faucibus vitae augue. Duis tempus, dui posuere ultricies finibus, lorem est hendrerit dui, id commodo odio ligula non diam. Etiam nec fermentum lorem.
  
  Nulla quis tempor libero. Morbi sit amet orci odio. Donec pretium ex quis elit laoreet, consequat suscipit est sagittis. Maecenas porta augue risus, nec vehicula massa feugiat nec. Praesent dictum nec nisl interdum efficitur. Morbi lacinia scelerisque eros quis efficitur. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Aliquam a commodo diam. Donec non purus sapien. Nulla at justo nisl. Curabitur eleifend magna at nibh commodo laoreet. Etiam vehicula vehicula sapien, a ullamcorper ex placerat sed. Cras blandit, metus commodo feugiat tristique, orci ipsum dapibus ante, sed gravida risus tortor a urna. Nulla pharetra tempus nisi et imperdiet.
  
  Suspendisse sit amet magna at nulla laoreet euismod. Aliquam erat volutpat. Vivamus placerat cursus suscipit. Aliquam euismod felis vel dolor iaculis, vitae efficitur mauris dictum. Donec facilisis ex vel est pulvinar, eget vestibulum massa ultrices. Proin scelerisque quam gravida imperdiet cursus. Donec eu placerat nibh. Duis ultrices nulla eu scelerisque commodo. Duis imperdiet et mi non tempor. Sed consequat ipsum eget elit pulvinar placerat. Vestibulum porttitor tincidunt nisi, eu luctus arcu tempus quis. Sed sagittis, dui aliquam elementum ultricies, libero neque ultrices justo, non convallis urna tortor quis risus. Donec pretium ipsum sed metus elementum bibendum. Praesent sed congue arcu.`,
};

export const NoGutter = Template.bind({});
NoGutter.args = {
  ...Default.args,
  noGutter: true,
};
