// Materialize-css init
M.AutoInit();

// Work with menu
ShowMenuItem();
AddButtonForExpandableMenuItems();
SetDefaultActiveOnFirstLevel();
SetDefaultActiveOnSecondLevel();

// Load and Parse index.json for lunr searching
ReadTextFile('../../../../../../index.json');
RenderResults();

// Reduce codes to Code-Blocks
BuildMenuAndWrapCodeExamples();