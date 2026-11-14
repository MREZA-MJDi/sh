import { initVideoPlayers } from './video-player';
import { initTabs } from './tabs';
import { initCoursePlayer } from './course-player';

document.addEventListener('DOMContentLoaded', () => {
    initVideoPlayers();
    initTabs();
    initCoursePlayer();
});
