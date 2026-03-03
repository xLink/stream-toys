import store from "@/Stores";

export default (key) => {
  // TODO: change to env
  let debugFlags = store.getters['app/debug'];

  if (!(key in debugFlags)) {
    return null;
  }

  return debugFlags[key];
};
