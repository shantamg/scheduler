<?php
/* SVN FILE: $Id$ */
/**
 * Short description for file.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework (http://www.cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://www.cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.app.config
 * @since         CakePHP(tm) v 0.2.9
 * @version       $Revision$
 * @modifiedby    $LastChangedBy$
 * @lastmodified  $Date$
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
	Router::connect('/', array('controller' => 'areas', 'action' => 'schedule'));

	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));
	
	Router::connect('/areas/request/edit/*', array('controller' => 'RequestAreas', 'action' => 'edit'));
	Router::connect('/areas/request/view/*', array('controller' => 'RequestAreas', 'action' => 'view'));
	Router::connect('/assignments/request/assign/*', array('controller' => 'RequestAssignments', 'action' => 'assign'));
	Router::connect('/shifts/request/delete/*', array('controller' => 'RequestShifts', 'action' => 'delete'));
	Router::connect('/areas/request/publish/*', array('controller' => 'RequestAreas', 'action' => 'publish'));
?>