# SpaceHuddle
SpaceHuddle: Game Assisted Brainstorming. A new approach to brainstorming with multiple people.

## SpaceHuddle API

SpaceHuddle uses a REST API for exchanging data between the different parts of the application (backend, moderator, client). The API's code is located in the `api` directory.

### Requirements

The SpaceHuddle API requires the following setup:

- a web server: tested with [Apache 2.4](https://httpd.apache.org/),
- an SQL database instance: tested with [MariaDB 10.4](https://mariadb.org/),
- [PHP 8.0](https://www.php.net/),
- [Composer](https://getcomposer.org/) dependency manager.

### Installation and running of the API

1. Install a local web development environment that meets the requirements above. [XAMPP](https://www.apachefriends.org/) works well, setups using [Vagrant](https://www.vagrantup.com/), [Docker](https://www.docker.com/), or other virtual machines will work as well.
2. Check out the GitHub project on your webserver (for XAMPP for example in the directory `\xampp\htdocs\SpaceHuddle`).
3. If not included in your setup, install Composer following the [instructions](https://getcomposer.org/download/) for your operating system.
4. Open a shell/terminal/command prompt, change to the `api` directory and install the dependencies by calling `composer install`.
5. Import `api/resources/schema.sql` into your database. This will create a database called "spacehuddle". Create a MySQL user with full permissions on the database. Enter your database credentials in `api/config/env.php` (see item 7).
6. Create a public and private key and copy them into the directory `api/resources/keys`
    -  `openssl genrsa -out private.pem 2048`
    -  `openssl rsa -in private.pem -outform PEM -pubout -out public.pem`
7. Copy `api/config/env.example.php` to `api/config/env.php` and adjust the properties
8. Start web server and database

### API Documentation

API documentation and testing are done using [Swagger](https://swagger.io/). The documentation is located in `api/docs/v1`.

To run it, point your browser to <http://{hostname}/{path}/api/docs/v1>, e.g., <http://localhost/api/docs/v1> or <http://localhost/SpaceHuddle/api/docs/v1>.

To test the various API endpoints, select one from the list, adapt the proposed request body if necessary and press "Execute". You will see the server response below.

To test for the *moderator* or *facilitator*, use the following steps:

1. call `/api/user/register/` and  enter any details for `username`, `password` and `password_confirmation`,
2. call`/api/user/login` and enter your username and password,
3. copy the value for `access_token`, click the button "Authorize" in the upper right corner and enter the token in the field for `bearerAuth`,
4. execute any arbitrary REST call for the moderator tool.

To test for a *participant*, use the following steps:

1. call `api/session` to create a new session with given details and copy the received value for `connection_key`,
2. call `/api/participant/connect/` and enter the key in `session_key`,
3. copy the value for `access_token`, click the button "Authorize" in the upper right corner and enter the token in the field for `bearerAuth`,
4. execute any arbitrary REST call for the client tool.

### Technology stack

The SpaceHuddle-API is built on the following technologies. Visit the websites to learn more about their use.

- PHP 8.0: programming language `https://www.php.net/`
- Slim: micro framework for PHP `https://www.slimframework.com/`
- CakePHP: database query framework `https://cakephp.org/`
- Swagger-PHP: REST API documentation `https://zircote.github.io/swagger-php/`

## SpaceHuddle frontend

### Installation
1. Adjust the properties in the `frontend/.env` file
2. Download and install Node `https://nodejs.org/en/download/`. Use a Node version that is lower or equal to 19.2.0
3. Install dependencies with: `npm install`

### SpaceHuddle games

The SpaceHuddle games are built using the javascript game framework `melonJS`, with physics handling done in `Matter.js`.
While the combination of these two frameworks can achieve amazing things, there are some notable caveats. The most
important one is regarding launching the game more than once. The issue itself and a workaround can be found in 'The Issue'.

##### Versions

- melonJS: `15.1.6`
- Matter.js: `0.19.0`

#### The Issue

As of the currently stated version, `melonJS` is a framework which by default starts and initializes only once, as soon as possible.
Once the engine is initialized, a single game instance has been created and the game is playable vía WebGL/Canvas directly
in your browser. Refreshing (F5) or leaving and re-entering the page re-starts the game without any issue since the entire page
has to be requested from the server.

In asynchronous environments, however, the active game instance will not be destroyed upon leaving the game and every subsequent
call for `melonJS` still uses the old and already redundant information for initialization, skipping a lot of important steps
while doing so and thus rendering the game unplayable.

#### The Workaround

Unfortunately, this workaround is a bit more involved since it requires editing of the `melonjs` module which npm delivered upon calling `npm install`,
however, it saves you and your users a lot of time. The following steps ensure proper subsequent re-initializations:
1. Head to `frontend\node_modules\melonjs\dist`. Inside the `types` folder you will find `index.d.ts` - open it in 
         an editor which allows you to modify and save type definitions (e.g. NotePad++).
2. Look for `export const game: Application;` and change it to `export let game: any;`. Adding this as a new line and
   commenting the former statement works as well. Save the file. This change keeps the global `game` instance but allows subsequent
   re-initializations.
3. Open `melonjs.module.js`. It is a big file so it might take a while to finish loading. Look for `let skipAutoInit = false;`
   (at or around line 38258). Change its value to `true`.
4. Just below you will find `const game = new Application(...);`. Change this to `let game = null`;
5. Below this statement the function `boot()` can be found. Inside the initial if-Statement, remove the return statement and substitute it with the
   following lines of code (you may omit the comments):
   ```
   // These lines successfully re-initialize the entire engine. No errors have been noticed in subsequent engine start-ups.
   // IMPORTANT: The ORDER of these calls is crucial!
         
   initialized = false;

   // Reset the Object Pool and Event Emitter.
   pool = new ObjectPool();
   eventEmitter = new EventEmitter();

   // Clear the renderer to allow re-init from a clean slate.
   renderer = null;

   // Clear all registered event handlers.
   eventHandlers = new Map();

   // Reset the status of registered pointer events.
   pointerInitialized = false;
   normalizedEvents = [];
   pointerEventTarget = null;
   pointer = new Pointer(0, 0, 1, 1);

   // Resetting camera/viewport configuration.
   targetV = new Vector2d();
   default_camera = undefined;

   // Reset some counters and lists relevant for game management and overview.
   globalFloatingCounter = 0;

   levels = {};
   levelIdx = [];
   currentLevelIdx = 0;

   resourceCount = 0;
   loadCount = 0;
   timerId = 0;
   ```
6. Add `game = new Application(0, 0, {legacy:true});` after the if-Statement (before `console.log(...);`).
7. Save the module. Stop the Dev-Environment if it is running. Re-Execute `npm run serve`.

### Compiles and hot-reloads for development
You can use either `npm start` or `npm run serve`.

### Compiles and minifies for production
```
npm run build
```

### Run your unit tests
```
npm run test:unit
```

### Lints and fixes files
```
npm run lint
```

### Technology stack

SpaceHuddle is built on the following technologies. Visit the websites to learn more about their use.

- Typescript: programming language `https://www.typescriptlang.org/`
- Sass: css styling language `https://sass-lang.com/`
- ESLint: code linter `https://eslint.org/`
- VUE3: JavaScript Framework `https://vuejs.org/guide/introduction.html`
- Vue Class Component: class-style syntax `https://class-component.vuejs.org/`
- Element Plus: component library `https://element-plus.org/en-US/component/button.html`
- Bulma: responsive web interfaces `https://bulma.io/`
- Font Awesome: icon library `https://fontawesome.com/`
- i18n: translation module `https://www.npmjs.com/package/i18n`
- Axios: backend access `https://axios-http.com/`
- Chart.js: chart components `https://www.chartjs.org/`
- Matter-js: physics engine `https://brm.io/matter-js/`

### Develop your own modules

1. Navigate to the folder `frontend/src/modules` and choose one of the following 5 subdirectory depending on the module type to be developed.
   1. information: information phase preceding the brainstorming (e.g. inspirational material, explaining the initial situation, evaluating the initial state (quiz, survey)).
   2. brainstorming: idea collection
   3. categorisation: structuring ideas
   4. selection: restrict ideas for further use
   5. voting: evaluate ideas
2. Create your own module subdirectory in the desired type folder.
3. Configure module
   1. Create `config.json` file within your module folder
   2. Set the properties required for your module in the json file
      - `icon`: name of the fontawesome icon to be assigned to the module (`https://fontawesome.com/`)
      - `iconPrefix`: optional if the icon category is not fas
      - `type`: choose one of the two options `main` or `addOn`. `main` modules stand alone. `addOn`s extend any `main` module of the same type.
      - `input`: Input indicates whether the module uses other modules as an input source. Choose one of the three option `yes`, `no`, `optional`.
      - `syncPublicParticipant`: Indicates whether the flow of the client module can be controlled by the moderator. Choose one of the two option `true`, `false`.
      - `fallback`: optional if a module extends another module
4. Set up multilingualism for module
   1. Create a `locals` folder within your module folder.
   2. Add a `[language abbreviation].json` to the `locales` folder for all available languages.
   3. Structure of the language files
      ```
      {
         "description": {
            "title": "...",
            "description": "..."
         },
         "publicScreen": {
            "...": "...",
            "...": "..."
         },
         "participant": {
            "...": "...",
            "...": "..."
         },
         "moderatorContent": {
            "...": "...",
            "...": "..."
         },
         "moderatorConfig": {
            "...": "...",
            "...": "..."
         }
      }
      ```
   4. The sections `publicScreen`, `participant`, `moderatorContent`, `moderatorConfig` are optional and should only help to structure the code.
   5. Replace the `"..."` information with your own content.
   6. The translation text can be embedded in the vue code as follows.
      ```
      $t('module.!moduletype!.!modulename!.!outputType!.!translationKey!')
      ```
      - `!moduletype!`: Specifies the name of the module type folder (`selection`, `categorisation`, `brainstorming`, `information`, `voting`)
      - `!modulename!`: Specifies the name of the module folder
      - `!outputType!`: Specifies the view name (`publicScreen`, `participant`, `moderatorContent`, `moderatorConfig`)
      - `!translationKey!`: Specifies the translation key
5. Develop your module.
   1. Create a `output` folder within your module folder.
   2. Create a `ModeratorContent.vue` file in the `output` folder if you need a moderator view in your module that differs from the default view.
      In the following example, replace the information between ! and !, and expand the functionality according to individual needs.
      ```
      <template>
      !html section!
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop } from 'vue-property-decorator';
      import { IModeratorContent } from '@/types/ui/IModeratorContent';

      @Options({
        components: {},
      })
      export default class ModeratorContent extends Vue implements IModeratorContent {
        @Prop() readonly taskId!: string;
      }
      </script>

      <style lang="scss" scoped>
      !scss section!
      </style>
      ```
   3. Create a `ModeratorConfig.vue` file in the `output` folder if you need individual adjustable configuration parameters for the moderator in your module.
      In the following example, replace the information between ! and !, and expand the functionality according to individual needs.
      ```
      <template>
         <el-form-item
         :label="$t('module.!moduletype!.!modulename!.moderatorConfig.!parametername!')"
         :prop="`${rulePropPath}.!parametername!`"
         :rules="[defaultFormRules.ruleRequired]"
         >
          <el-input-number
            v-model="modelValue.!parametername!"
            :placeholder="$t('module.!moduletype!.!modulename!.moderatorConfig.!parameternameExample!')"
          />
        </el-form-item>
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop, Watch } from 'vue-property-decorator';
      import * as moduleService from '@/services/module-service';
      import { Module } from '@/types/api/Module';
      import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
      import * as cashService from '@/services/cash-service';

      @Options({
        components: {},
      })

      /* eslint-disable @typescript-eslint/no-explicit-any*/
      export default class ModeratorConfig extends Vue {
        defaultFormRules: ValidationRuleDefinition = defaultFormRules;
        @Prop() readonly rulePropPath!: string;

        @Prop() readonly moduleId!: string;
        @Prop() readonly taskId!: string;
        @Prop() readonly topicId!: string;
        @Prop({ default: {} }) modelValue!: any;
        module: Module | null = null;

        @Watch('modelValue', { immediate: true })
        async onModelValueChanged(): Promise<void> {
          if (this.modelValue && !this.modelValue.!parametername!) {
            this.modelValue.!parametername! = !parameternameDefaultValue!;
          }
        }

        @Watch('moduleId', { immediate: true })
        async onModuleIdChanged(): Promise<void> {
          if (this.moduleId) {
            moduleService.registerGetModuleById(
              this.moduleId,
              this.updateModule,
              EndpointAuthorisationType.MODERATOR,
              60 * 60
            );
          }
        }

        updateModule(module: Module): void {
          this.module = module;
        }
      
        deregisterAll(): void {
          cashService.deregisterAllGet(this.updateModule);
        }

        unmounted(): void {
          this.deregisterAll();
        }
      }
      </script>
      ```
   4. Create a `participant.vue` file in the `output` folder if you need a participant view for your module that differs from the default view.
      In the following example, replace the information between ! and !, and expand the functionality according to individual needs.
      ```
      <template>
        <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
          !html section!
        </ParticipantModuleDefaultContainer>
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop, Watch } from 'vue-property-decorator';
      import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
      import * as moduleService from '@/services/module-service';
      import { Module } from '@/types/api/Module';
      import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
      import * as cashService from '@/services/cash-service';

      @Options({
        components: {
          ParticipantModuleDefaultContainer,
        },
      })
      export default class Participant extends Vue {
        @Prop() readonly taskId!: string;
        @Prop() readonly moduleId!: string;
        @Prop({ default: false }) readonly useFullSize!: boolean;
        @Prop({ default: '' }) readonly backgroundClass!: string;
        module: Module | null = null;

        get moduleName(): string {
          if (this.module) return this.module.name;
          return '';
        }

        @Watch('moduleId', { immediate: true })
        onModuleIdChanged(): void {
          if (this.moduleId) {
            moduleService.registerGetModuleById(
              this.moduleId,
              this.updateModule,
              EndpointAuthorisationType.PARTICIPANT,
              60 * 60
            );
          }
        }

        updateModule(module: Module): void {
          this.module = module;
        }
      
        deregisterAll(): void {
          cashService.deregisterAllGet(this.updateModule);
        }

        unmounted(): void {
          this.deregisterAll();
        }
      }
      </script>

      <style lang="scss" scoped>
      !scss section!
      </style>

      ```
   5. Create a `PublicScreen.vue` file in the `output` folder if you need an individual public screen for your module that differs from the default view.
      In the following example, replace the information between ! and !, and expand the functionality according to individual needs.
      ```
      <template>
      !html section!
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop } from 'vue-property-decorator';
      import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

      @Options({
        components: {},
      })
      export default class PublicScreen extends Vue {
        @Prop() readonly taskId!: string;
        @Prop({ default: EndpointAuthorisationType.MODERATOR })
        authHeaderTyp!: EndpointAuthorisationType;
      }
      </script>

      <style lang="scss" scoped>
      !scss section!
      </style>
      ```
   6. Develop additional components and types required for the module within the module folder. Spacehuddle uses atomic design to structure the files. Therefore, depending on what is required, structure the subdirectories into `types`, `organisms`, `molecules` and `atoms`. All these folders are optional.
   7. The implementation of the access to the backend interfaces can be found in the folder `frontend/src/services`. To illustrate their use, here is an example implementation for querying all ideas of a task.
      ```
      <template>
      !html section!
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop } from 'vue-property-decorator';
      import { IModeratorContent } from '@/types/ui/IModeratorContent';
      import * as ideaService from '@/services/idea-service';
      import { Idea } from '@/types/api/Idea.ts';
      import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
      import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
      import * as cashService from '@/services/cash-service';

      @Options({
        components: {},
      })
      export default class ModeratorContent extends Vue implements IModeratorContent {
        @Prop() readonly taskId!: string;
        ideas: Idea[] = [];
        activeIdea!: Idea;

        @Watch('taskId', { immediate: true })
        onTaskIdChanged(): void {
          ideaService.registerGetIdeasForTask(
            this.taskId,
            IdeaSortOrder.TIMESTAMP,
            null,
            this.updateIdeas,
            EndpointAuthorisationType.MODERATOR,
            2 * 60
          );
        }

        updateIdeas(ideas: Idea[]): void {
          this.ideas = ideas;
        }
      
        deregisterAll(): void {
          cashService.deregisterAllGet(this.updateIdeas);
        }

        unmounted(): void {
          this.deregisterAll();
        }

        async save(): Promise<void> {
          if (this.activeIdea.id) {
             await ideaService
                .putIdea(this.activeIdea, EndpointAuthorisationType.MODERATOR)
                .then((queryResult) => {
                   //todo
                });
          } else if (this.taskId) {
             await ideaService
             .postIdea(this.taskId, this.activeIdea, EndpointAuthorisationType.MODERATOR)
             .then((queryResult) => {
                if (queryResult) {
                 this.activeIdea = {};
                 this.ideas.push(queryResult);
               }
             });
          }
        }
      }
      </script>

      <style lang="scss" scoped>
      !scss section!
      </style>
      ```
      - `GET` is used to read data. Get calls are implemented by registering them at the client-side cash to prevent multiple loading of data by different components. An update interval can be specified by which the data is reloaded from the backend. If no regular update is to take place, the value is to be set to 24 * 60 * 60 seconds = 1 day. It is important to disable the registration when leaving the page, otherwise the update will continue.
      - `POST` is used for the initial insertion of data.
      - `PUT` for changing already inserted data.
      - `DELETE` for deleting data.
   8. Under `frontend/src/components` custom VUE components such as entity cards (e.g. `IdeaCard`), entity change dialogs (e.g. `IdeaSettings`) or layout components (e.g. `ParticipantModuleDefaultContainer`) can be found.
      ```
      <template>
        <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
          ...
         <div class="media" v-for="idea in ideas" :key="idea.id">
            <IdeaCard
                :idea="idea"
                :is-editable="false"
                class="public-idea"
                :show-state="false"
              />
         </div>
          ...
        </ParticipantModuleDefaultContainer>
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop, Watch } from 'vue-property-decorator';
      import { Module } from '@/types/api/Module';
      import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
      import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
      import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

      @Options({
        components: {
            ParticipantModuleDefaultContainer,
            IdeaCard
        },
      })
      export default class Participant extends Vue {
        @Prop() readonly taskId!: string;
        @Prop() readonly moduleId!: string;
        @Prop({ default: false }) readonly useFullSize!: boolean;
        @Prop({ default: '' }) readonly backgroundClass!: string;
        module: Module | null = null;
        ideas: Idea[] = [];

        get moduleName(): string {
          if (this.module) return this.module.name;
          return '';
        }

        ...
      }
      </script>

      <style lang="scss" scoped>
      .public-idea {
         max-width: 20rem;
      }
      ...
      </style>

      ```
      If these components are to be used in the module in a way that differs from the predefined implementation, we kindly request that you implement an individual development of the components in the module folder. The components can be copied as a template for this purpose.
   9. If data should be updated automatically from the backend, this can be solved by registering at client side cash. It is important to disable the registration when leaving the page, otherwise the update will continue.
      ```
      <template>
      !html section!
      </template>

      <script lang="ts">
      import { Options, Vue } from 'vue-class-component';
      import { Prop } from 'vue-property-decorator';
      import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
      import * as cashService from '@/services/cash-service';

      @Options({
        components: {},
      })
      export default class PublicScreen extends Vue {
        @Prop() readonly taskId!: string;
        @Prop({ default: EndpointAuthorisationType.MODERATOR })
        authHeaderTyp!: EndpointAuthorisationType;

        @Watch('taskId', { immediate: true })
        onTaskIdChanged(): void {
          ideaService.registerGetIdeasForTask(
            this.taskId,
            IdeaSortOrder.TIMESTAMP,
            null,
            this.updateIdeas,
            EndpointAuthorisationType.MODERATOR,
            2 * 60
          );
        }

        updateIdeas(ideas: Idea[]): void {
          this.ideas = ideas;
        }
      
        deregisterAll(): void {
          cashService.deregisterAllGet(this.updateIdeas);
        }

        unmounted(): void {
          this.deregisterAll();
        }
      }
      </script>

      <style lang="scss" scoped>
      !scss section!
      </style>
      ```
