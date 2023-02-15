import Image from '/src/components/ThemedImg';

# JetBrains plugin

For smooth development we've created a Php/WebStorm Orchesty plugin (currently supports only Node.js).

## Features

- File templates (Applications, Nodes, Tests)
- Test generation / deletion with helper files
- Joi schema generation
- NamingConventions helper

### Installation

Find **Orchesty** in marketplace and install it.
<Image path="/img/plugin/install.png" />

### File templates

Adds two new options into new file dialog for Nodes and Application files.
<Image path="/img/plugin/newFile.png" />

#### New Node:

<Image path="/img/plugin/newNode.png"/>

- Filename - filename without .ts extension
- NodeName - corresponding Node's name used as a identifier
- Template - type of Node (Connector, CustomNode, Batch) or corresponding test
  - Plugin pre-selects correct type based on which folder you use it (**Connector**, **Batch**, **CustomNode**) or (**\_\_tests\_\_**) under corresponding folders
- With test - for Node templates generates also test & data (.json) files under **\_\_test\_\_** folder
- With (beforeEach, afterEach, afterAll) - generates Jest's method into @describe block
  - beforeAll is used by default

#### Node Application

<Image path="/img/plugin/newApplication.png"/>

- Application name - Used for both Application & Filename
- Auth type - currently not supported selection of Basic/OAuth/OAuth2 applications
- With webhooks - adds implementation of webhook application interface

#### Test case generation / removal

Within Node's test you can easily create a new test case via Alt+Insert generate menu.  
New test optional will create both jest's **it('')** and Orchesty's **.json** files.

<Image path="/img/plugin/newTest.png"/>

Removal of existing test can be done via Alt+Enter menu, it will also look for corresponding **.json**
files are removes them as well.  
Make sure you're inside a **it('')** block.

<Image path="/img/plugin/removeTest.png"/>

#### Joi schema validation

Orchesty offers **@validate** decorator to ensure incoming data into Node are correct. The basic Joi object used for validation
(together with decorator itself) can be auto-generated from interface within Node's file. Open context menu Alt+Enter with
cursor inside interface and select **Generate Joi..**.

<Image path="/img/plugin/joi.png"/>

Generated schema

<Image path="/img/plugin/schema.png"/>

...which is fold-able to lower code bloat. This region is folded by default (except for newly generated or unfolded).

<Image path="/img/plugin/foldedSchema.png"/>

#### Naming conventions

While Orchesty follows camelCase naming conventions, many systems do not. For cases when your Connector is working
with different naming convention, it's often necessary to disable linter for given case, for that you can you Alt+Enter
context menu (make sure you're inside an object **{}** block) and plugin will wrap it with **disable** **enable** comments.

<Image path="/img/plugin/naming.png"/>
