/* global pl_script_data */

// General Script loader.
( function() {
	var loadMap = {};
	var timerStart = 0;
	var loadTimeout;

	let getQueryVars = function() {
		var string = window.location.href;
		var vars = {};
		var hash = [];

		if ( string.search(/\?/i) === -1 ) return [];

		// Store GET params as vars[]
		var hashes = string.slice(string.indexOf('?') + 1).split('&');
		for( var i = 0; i < hashes.length; i++ ) {
			hash = hashes[i].split('=');
			vars[ hash[0] ] = hash[1];
		}
		return vars;
	};

	console.log( 'Promising Loader V1' );

	if ( getQueryVars().hasOwnProperty( 'noscript' ) ) {
		console.log( 'ALL WSS SCRIPTS DISABLED' );
		return;
	}

	let allScripts = pl_script_data.sources.async.concat( pl_script_data.sources.defer ).concat( pl_script_data.sources.normal );

	allScripts.forEach( resource => {
		resource.promise = new Promise( resolve => resolve( true ) )
		loadMap[ resource.handle ] = resource;
		if ( resource.handle === 'jquery-core' ) {
			loadMap[ 'jquery' ] = resource;
		}
	} );

	pl_script_data.sources.onload.forEach( function( resource ) {
		resource.promise = null;
		loadMap[ resource.handle ] = resource;
	} );

	let loadResources = function( resource ) {
		let requiredPromises = [];

		resource.deps.forEach( dep => {
			if ( 'jquery' === dep ) {
				dep = 'jquery-core';
			}

			loadResources( loadMap[ dep ] );
			requiredPromises.push( loadMap[ dep ].promise );
		} );

		if ( loadMap[ resource.handle ].promise === null ) {
			loadMap[ resource.handle ].promise = Promise.all( requiredPromises ).then( () => {
				return new Promise( ( resolve, reject ) => {
					var s = document.createElement( 'script' );
					s.async = 1;
					s.src = resource.src;
					s.addEventListener( 'load', () => {
						console.log( 'PL Script Loaded', resource.handle, 'onload Δ', ( performance.now() - timerStart).toFixed() );
						resolve( resource.handle );
					} );
					setTimeout( () => {
						document.body.appendChild( s );
					}, resource.delay );
				} );
			} );
		}
	};

	let startLoadSequence = function () {
		let delayInSeconds = pl_script_data.delayAfterWindowLoad;
		let queryVars = getQueryVars();
		
		const event = new Event( 'loadOrStart' );
		window.dispatchEvent( event );

		clearTimeout( loadTimeout );

		if ( queryVars.hasOwnProperty( 'delay' ) ) {
			console.log( 'OVERRIDE DELAY', queryVars.delay );
			delayInSeconds = parseInt( queryVars.delay );
		}
		timerStart = performance.now();
		console.log( 'PL window.onload fired at:', timerStart.toFixed() );
		
		setTimeout( function() {
			console.log( 'PL start loading scripts', 'onload Δ', ( performance.now() - timerStart).toFixed() );
			pl_script_data.sources.onload.forEach( function( resource ) {
				loadResources( resource );
			} );
		}, delayInSeconds );
	}

	window.addEventListener( 'load', startLoadSequence );

	// Failsafe in case window.load takes too long.
	loadTimeout = setTimeout( function() {
		console.log( 'PL - Failsafe triggered. Onload was taking too long. Δ', ( performance.now() - timerStart).toFixed() );
		window.removeEventListener( 'load', startLoadSequence );
		startLoadSequence();
	}, pl_script_data.failsafe );

	pl_script_data.getDependencyOf = function( handle ) {
		let dependents = [];
		for ( h in loadMap ) {
			if ( loadMap[h].deps ) {
				if ( loadMap[h].deps.indexOf( handle ) > -1 ) {
					dependents.push( h );
				}
			}
		}
	
		return dependents;
	};	
})();
