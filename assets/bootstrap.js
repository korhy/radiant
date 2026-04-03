import { Application } from '@hotwired/stimulus';
import TaquinController from './controllers/taquin_controller.js';
import AppDetailDrawerController from './controllers/app_detail_drawer_controller.js';
import RecipeChatController from './controllers/recipe_chat_controller.js';


const app = Application.start();
app.register('taquin', TaquinController);
app.register('app-detail-drawer', AppDetailDrawerController);
app.register('recipe-chat', RecipeChatController);
