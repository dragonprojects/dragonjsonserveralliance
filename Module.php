<?php
/**
 * @link http://dragonjsonserver.de/
 * @copyright Copyright (c) 2012-2014 DragonProjects (http://dragonprojects.de/)
 * @license http://license.dragonprojects.de/dragonjsonserver.txt New BSD License
 * @author Christoph Herrmann <developer@dragonprojects.de>
 * @package DragonJsonServerAlliance
 */

namespace DragonJsonServerAlliance;

/**
 * Klasse zur Initialisierung des Moduls
 */
class Module
{
    use \DragonJsonServer\ServiceManagerTrait;
    
    /**
     * Gibt die Konfiguration des Moduls zurück
     * @return array
     */
    public function getConfig()
    {
        return require __DIR__ . '/config/module.config.php';
    }

    /**
     * Gibt die Autoloaderkonfiguration des Moduls zurück
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }
    
    /**
     * Wird bei der Initialisierung des Moduls aufgerufen
     * @param \Zend\ModuleManager\ModuleManager $moduleManager
     */
    public function init(\Zend\ModuleManager\ModuleManager $moduleManager)
    {
    	$sharedManager = $moduleManager->getEventManager()->getSharedManager();
    	$sharedManager->attach('DragonJsonServerApiannotation\Module', 'Request', 
	    	function (\DragonJsonServerApiannotation\Event\Request $eventRequest) {
	    		$annotation = $eventRequest->getAnnotation();
	    		if (!$annotation instanceof \DragonJsonServerAlliance\Annotation\Alliance) {
	    			return;
	    		}
	    		$serviceManager = $this->getServiceManager();
	    		$avatar = $serviceManager->get('\DragonJsonServerAvatar\Service\Avatar')->getAvatar();
	    		if (null === $avatar) {
	    			throw new \DragonJsonServer\Exception('missing avatar');
	    		}
	    		$serviceAllianceavatar = $serviceManager->get('\DragonJsonServerAlliance\Service\Allianceavatar');
	    		$allianceavatar = $serviceAllianceavatar->getAllianceavatarByAvatar($avatar);
	    		$serviceAllianceavatar->setAllianceavatar($allianceavatar);
	    		$role = $allianceavatar->getRole();
	    		$roles = $annotation->getRoles();
	    		if (null !== $roles && !in_array($role, $roles)) {
		    		throw new \DragonJsonServer\Exception(
		    			'invalid role', 
		    			['allianceavatar' => $allianceavatar->toArray(), 'roles' => $roles]
		    		);
	    		}
	    		$notroles = $annotation->getNotroles();
	    		if (in_array($role, $notroles)) {
		    		throw new \DragonJsonServer\Exception(
		    			'invalid role', 
		    			['allianceavatar' => $allianceavatar->toArray(), 'notroles' => $notroles]
		    		);
	    		}
	    	}
    	);
    	$sharedManager->attach('DragonJsonServerApiannotation\Module', 'Request', 
	    	function (\DragonJsonServerApiannotation\Event\Request $eventRequest) {
	    		$annotation = $eventRequest->getAnnotation();
	    		if (!$annotation instanceof \DragonJsonServerAlliance\Annotation\Noalliance) {
	    			return;
	    		}
	    		$serviceManager = $this->getServiceManager();
	    		$avatar = $serviceManager->get('\DragonJsonServerAvatar\Service\Avatar')->getAvatar();
	    		if (null === $avatar) {
	    			throw new \DragonJsonServer\Exception('missing avatar');
	    		}
	    		$allianceavatar = $serviceManager->get('\DragonJsonServerAlliance\Service\Allianceavatar')->getAllianceavatarByAvatar($avatar, false);
	    		if (null === $allianceavatar) {
	    			return;
	    		}
	    		throw new \DragonJsonServer\Exception(
	    			'avatar already allianceavatar', 
	    			['allianceavatar' => $allianceavatar->toArray()]
	    		);
	    	}
    	);
		$sharedManager->attach('DragonJsonServerAvatar\Service\Avatar', 'RemoveAvatar',
			function (\DragonJsonServerAvatar\Event\RemoveAvatar $eventRemoveAvatar) {
				$serviceManager = $this->getServiceManager();
				$serviceAllianceavatar = $serviceManager->get('\DragonJsonServerAlliance\Service\Allianceavatar');
				$allianceavatar = $serviceAllianceavatar->getAllianceavatarByAvatar($eventRemoveAvatar->getAvatar(), false);
				if (null === $allianceavatar) {
					return;
				}
				if ($serviceAllianceavatar->validateSecondLeader($allianceavatar, false)) {
					$serviceAllianceavatar->removeAllianceavatar($allianceavatar);
				} else {
					$serviceAlliance = $serviceManager->get('\DragonJsonServerAlliance\Service\Alliance');
					$alliance = $serviceAlliance->getAllianceByAllianceId($allianceavatar->getAllianceId());
					$serviceAlliance->removeAlliance($alliance);
				}
			}
		);
    }
}
