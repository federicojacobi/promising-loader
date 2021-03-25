# Promising Loader Plugin
It is promising, because it uses promises, and the results are also very promising!

Load all WordPress scripts after then `window.load` event. Script are loadead maintaining the dependencies set up in php.

All the scripts will be loaded asynchronously as fast as possible unless specific timing is set up.


# Inner workings
When a script is enqueued, WordPress creates a dependency tree so the script tags can be added in the right order. We take a similar approach, but move the whole tree to the front-end and load the script after the window.load event.

Scripts are added to the DOM as soon as possible and are added async, so they all load at the same time. To prevent JS breaking, the dependents are loaded once the "parent" is loaded recursively via promises. Each promise waits for the parent and for its defined delay.

The advantage to this approach is that no changes are needed neither on PHP nor on JS. Everything works as it always did ... only later in the timeline.

You can control the flow of scripts by adding delays, but the delays always respect dependencies and are relative to the parent.


# Filters

pl_script_load_normal - List of script handles to be loaded normally.

`add_filter( 'pl_script_load_normal', function( $handles = [] ) { return $handles; } );`

---

pl_script_load_defer - Mark a script handle to be 'defer'.

`add_filter( 'pl_script_load_defer', function( $deferred_scripts = [] ) { return $deferred_scripts; } );`

---

pl_script_def_async - Mark a script handle to be async.

`add_filter( 'pl_script_def_async', function( $script_def = [] ) { return $script_def; } );`

---

pl_script_load_windowload - All scripts not marked above will be loaded on window.load.

```
add_filter(
	'pl_script_load_windowload',
	function( $scripts ) { // $scripts is an array of script definitions ( 'handle' => args ).
		$scripts['my_script'] = [
			'delay' => 1000, // Once dependencies are in place, how long to wait before loading this script.
			'additional_deps' => [ 'jquery' ], // Force additional dependencies. Useful in cases where a plugin doesn't specify dependencies properly.
		];

		return $scripts;
	}
);
```

---

pl_skip_preload_css - All styles will be set to be preloaded unless added to the array this filter exposes.

`add_filter( 'pl_skip_preload_css', function( $styles = [] ) { return $styles; } );`

---

pl_preload_fonts - Array of font urls to be preloaded.

`add_filter( 'pl_preload_fonts', function( $font_urls = [] ) { return $font_urls; } );`

---

pl_preconnect_origins - Array of urls to do preconnect on.

`add_filter( 'pl_preconnect_origins', function( $preconnect_urls = [] ) { return $preconnect_urls; } );`

---

pl_preload_scripts - Experimental, preload scripts.


# Known bugs
Admin page doesn't work ... yeah, it is not done.