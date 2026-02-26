import { Application } from '@hotwired/stimulus';
import TaquinController from './controllers/taquin_controller.js';

const app = Application.start();
app.register('taquin', TaquinController);
