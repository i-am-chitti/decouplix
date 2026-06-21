import { createRoot } from '@wordpress/element';

/**
 * App component rendering the main configuration header.
 *
 * @return {JSX.Element} The configuration screen.
 */
const App = () => {
	return (
		<div className="hc-settings-container">
			<h1>Headless Companion &amp; Smart Purge</h1>
			<p>Placeholder: The React settings screen loaded successfully.</p>
		</div>
	);
};

// Wait for DOM to be fully loaded before rendering.
document.addEventListener( 'DOMContentLoaded', () => {
	const rootElement = document.getElementById( 'hc-settings-root' );
	if ( rootElement ) {
		const root = createRoot( rootElement );
		root.render( <App /> );
	}
} );
