import login from "./views/login.json";
import laborer from "./views/laborer.json";
import button from "./commons/button.json";
import table from "./commons/table.json";
import frequencyEnums from "./views/frequencyEnums.json";
import pageTitle from "./commons/pageTitle.json";

export default {
  ...frequencyEnums,
  ...pageTitle,
  ...button,
  ...login,
  ...laborer,
  ...table,
};
