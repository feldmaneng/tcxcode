<?php

namespace Config;

//use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Config\Filters as BaseFilters;

use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

//added from Lovable
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
		'forcehttps'    => ForceHTTPS::class,
		'pagecache'     => PageCache::class,
		'performance'   => PerformanceMetrics::class,
		
		'cors'     => \App\Filters\CorsFilter::class,
        'throttle' => \App\Filters\ThrottleFilter::class,
        'hmac'     => \App\Filters\HmacAuthFilter::class,
        'jwt'      => \App\Filters\JwtAuthFilter::class,
        'apiAuth'  => \App\Filters\ApiAuthFilter::class,
        'audit'    => \App\Filters\AuditLogFilter::class,
        'hmac'     => \App\Filters\HmacAuthFilter::class,
        
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     */
     //IMF Ref: https://www.codeigniter.com/user_guide/libraries/security.html#cross-site-request-forgery-csrf
     public array $globals = [
        'before' => [
            // 'honeypot',
           // 'csrf',
            'csrf' => ['except' => ['Jotformpost/', 'Guest/*', 'api/*']],
            // 'invalidchars',
        ],
        'after' => [
            
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don’t expect could bypass the filter.
     */
    //public array $methods = [];
	
	public array $methods = [
		'POST' => ['invalidchars', 'csrf'],
		'GET'  => ['csrf'],
	];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */
    
    public array $filters = [
        // Apply HMAC auth to every /api/v1/* route, all HTTP methods.
        'hmac' => [
            'before' => [
                'api/v1/*',
            ],
        ],
    ];
	
	public array $required = [
    'before' => [
        'forcehttps', // Force Global Secure Requests
        'pagecache',  // Web Page Caching
    ],
    'after' => [
        'pagecache',   // Web Page Caching
        'performance', // Performance Metrics
        'toolbar',     // Debug Toolbar
    ],
];
}
