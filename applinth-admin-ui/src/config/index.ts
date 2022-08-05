const config =
  process.env.NODE_ENV === "production"
    ? require("./config.prod").default // eslint-disable-line
    : require("./config.dev").default; // eslint-disable-line

export { config };
