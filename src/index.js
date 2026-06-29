import { createRoot, useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

// Configure apiFetch middleware with the localized WordPress REST parameters.
if ( window.hcSettings ) {
	apiFetch.use( apiFetch.createNonceMiddleware( window.hcSettings.nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( window.hcSettings.root ) );
}

import './admin.css';

/**
 * Main Settings application component.
 *
 * @return {JSX.Element} The settings application dashboard.
 */
const App = () => {
	const [ settings, setSettings ] = useState( {
		frontend_url: '',
		webhook_url: '',
		webhook_secret: '',
		cache_endpoints: '',
	} );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	// Fetch settings from REST API on mount.
	useEffect( () => {
		apiFetch( { path: 'decouplix/v1/settings' } )
			.then( ( data ) => {
				setSettings( data );
				setIsLoading( false );
			} )
			.catch( ( error ) => {
				setNotice( {
					type: 'error',
					message: error.message || 'Failed to load settings.',
				} );
				setIsLoading( false );
			} );
	}, [] );

	/**
	 * Save settings via POST REST request.
	 *
	 * @param {Event} e The form submit event.
	 */
	const handleSave = ( e ) => {
		e.preventDefault();
		setIsSaving( true );
		setNotice( null );

		apiFetch( {
			path: 'decouplix/v1/settings',
			method: 'POST',
			data: settings,
		} )
			.then( ( response ) => {
				setNotice( {
					type: 'success',
					message: response.message || 'Settings saved successfully.',
				} );
				if ( response.data ) {
					setSettings( response.data );
				}
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				setNotice( {
					type: 'error',
					message: error.message || 'Failed to save settings.',
				} );
				setIsSaving( false );
			} );
	};

	/**
	 * Regenerates a secure 32-character random hex webhook secret key.
	 */
	const generateSecret = () => {
		const chars =
			'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		let secret = '';
		for ( let i = 0; i < 32; i++ ) {
			secret += chars.charAt(
				Math.floor( Math.random() * chars.length )
			);
		}
		setSettings( { ...settings, webhook_secret: secret } );
	};

	if ( isLoading ) {
		return (
			<div
				className="hc-loading-container"
				style={ { padding: '40px 0', textAlign: 'left' } }
			>
				<Spinner />
				<span style={ { marginLeft: '10px', verticalAlign: 'middle' } }>
					Loading settings...
				</span>
			</div>
		);
	}

	return (
		<div
			className="hc-settings-wrap"
			style={ { maxWidth: '800px', margin: '20px 0' } }
		>
			<h2>Decouplix Settings</h2>
			<p className="description" style={ { marginBottom: '20px' } }>
				Configure your decoupled frontend configurations below to enable
				automatic caching purges, preview hooks, and secure webhook
				triggering.
			</p>

			{ notice && (
				<Notice
					status={ notice.type }
					onRemove={ () => setNotice( null ) }
					isDismissible
				>
					{ notice.message }
				</Notice>
			) }

			<form onSubmit={ handleSave } style={ { marginTop: '20px' } }>
				<PanelBody title="General Configuration" initialOpen={ true }>
					<div className="hc-form-row">
						<TextControl
							label="Front-end URL"
							help="The base URL of your decoupled frontend site, e.g. https://my-site.com. Used for frontend preview redirection and relative URL mapping."
							value={ settings.frontend_url }
							onChange={ ( val ) =>
								setSettings( {
									...settings,
									frontend_url: val,
								} )
							}
							type="url"
							placeholder="https://my-site.com"
						/>
					</div>

					<div className="hc-form-row">
						<TextControl
							label="Webhook URL"
							help="The custom URL where event webhooks should be sent, e.g., a Vercel Deploy Hook, Zapier, or a custom Next.js webhook path. Leave empty to disable."
							value={ settings.webhook_url }
							onChange={ ( val ) =>
								setSettings( {
									...settings,
									webhook_url: val,
								} )
							}
							type="url"
							placeholder="https://api.vercel.com/v1/integrations/deploy/..."
						/>
					</div>

					<div className="hc-form-row">
						<label
							className="components-base-control__label"
							htmlFor="hc-webhook-secret"
						>
							Webhook Secret
						</label>
						<div className="hc-webhook-row-container">
							<div className="hc-webhook-input-wrapper">
								<TextControl
									id="hc-webhook-secret"
									value={ settings.webhook_secret }
									onChange={ ( val ) =>
										setSettings( {
											...settings,
											webhook_secret: val,
										} )
									}
									hideLabelFromVision
									label="Webhook Secret"
								/>
							</div>
							<Button isSecondary onClick={ generateSecret }>
								Regenerate
							</Button>
						</div>
						<p className="components-base-control__help">
							Secret key used to securely sign outgoing webhook
							payloads sent to the frontend. The frontend can
							verify authenticity via HMAC signature check.
						</p>
					</div>

					<div className="hc-form-row">
						<TextareaControl
							label="Cache Revalidation Endpoints"
							help="Enter your frontend cache purge / revalidation endpoints (one per line). These will receive POST requests containing modified paths."
							value={ settings.cache_endpoints }
							onChange={ ( val ) =>
								setSettings( {
									...settings,
									cache_endpoints: val,
								} )
							}
							placeholder="https://my-site.com/api/revalidate"
							rows={ 4 }
						/>
					</div>
				</PanelBody>

				<div style={ { marginTop: '20px' } }>
					<Button isPrimary type="submit" disabled={ isSaving }>
						{ isSaving ? 'Saving...' : 'Save Settings' }
					</Button>
				</div>
			</form>
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
