import SocialShareButtons from 'social-share-buttons';
import 'social-share-buttons/styles';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('share-buttons');
    if (container) {
        new SocialShareButtons({
            container: '#share-buttons',
            platforms: ['x', 'facebook', 'linkedin'],
            // Translated labels come from lang/en/frontend.php via a data attribute (CSP-safe, no inline script).
            labels: JSON.parse(container.dataset.labels || '{}')
        });
    }
});
