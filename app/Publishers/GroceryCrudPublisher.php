<?php

// app/Publishers/GroceryCrudPublisher.php

namespace App\Publishers;

use CodeIgniter\Publisher\Publisher;

class GroceryCrudPublisher extends Publisher
{
    /**
     * Tell Publisher where to get the files.
     * Since we will use Composer to download
     * them we point to the "vendor" directory.
     *
     * @var string
     */
    protected $source = FCPATH . '/../vendor/grocery-crud/enterprise/public/';

    /**
     * FCPATH is always the default destination,
     * but just to be explicit, we'll set it here.
     *
     * @var string
     */
     
    // Need to update as our code base is OUTSIDE of public_html
	// Was unable to get this to work due to protections in Config/Publisher.php
	// Or to specify the public_html directory
	// So just have to copy from project testconx/public/vendor to ~/public_html/vendor
	// protected $destination = '/home/testconx/public_html/vendor/';
	// IMF 7/30/25
	protected $destination = FCPATH;
	    
	    
    /**
     * Use the "publish" method to indicate that this
     * class is ready to be discovered and automated.
     */
    public function publish(): bool
    {
        return $this
            // Add all the files relative to $source
            ->addPath('./')

            ->removePattern('#\.gitkeep#')
            ->removePattern('#\.txt#')

            // Merge-and-replace to retain the original directory structure
            ->merge(true);
    }
}
