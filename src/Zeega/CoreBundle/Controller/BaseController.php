<?php

/*
* This file is part of Zeega.
*
* (c) Zeega <info@zeega.org>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Zeega\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyBaseController;

/**
 * Controller is a simple implementation of a Controller that extends the Symfony base controller.
 *
 */
class BaseController extends SymfonyBaseController
{
    public function getUser($apiKey = null) {
        // get the logged user
        $user = parent::getUser();

        if ( null === $user ) {
            // if the logged user doesn't exist, check if there's an api key
            if ( null !== $apiKey ) {
                $em = $this->getDoctrine()->getEntityManager();
                $user = $em->getRepository("ZeegaDataBundle:User")->findOneBy( array("api_key" => $apiKey) );
                if( null !== $user ) {
                    return $user;
                }
            }
        } else {
            return $user;
        }

        return null;
    }

    protected function isItemOwner($item, $user)
    {
        if( null !== $user && null !== $item) {
            if( intval($user->getId()) === intval($item->getUserId()) ) {
                return true;
            }
        }
        
        return false;
    }

    protected function isInRole($role, $user = null)
    {
        if ( null === $role ) {
            return false;
        } 
        if ( !is_string($role) ) {
            throw new \BadFunctionCallException('The role parameter has to be a string');
        }

        if ( null === $user ) {
            if ( $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') ) {
                $loggedUserId = $this->container->get('security.context')->getToken()->getUser()->getId();                
                if ( $this->container->get('security.context')->isGranted($role) ) {
                    return true;
                }
            }
        } else {
            $userRoles = $user->getRoles();
            foreach($userRoles as $userRole) {
                if ( $userRole === $role ) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function isUserAdmin( $user = null )
    {
        return $this->isInRole( "ROLE_ADMIN", $user );
    }

    /**
     * Checks the logged user id matches the userId parameters and throws a not authorized
     * exception if the user is not authorized to access the resource.
     *
     * @param string  $userId     The target user id
     *
     */
    protected function authorize($userId)
    {
        if(null === $userId)
        {
            throw new \BadFunctionCallException('The userId parameter cannot be null');
        }
        
        if($this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') )
        {
            $loggedUserId = $this->container->get('security.context')->getToken()->getUser()->getId();
            
            if($loggedUserId == $userId || $this->container->get('security.context')->isGranted('ROLE_ADMIN'))
            {
                return;
            }
        }

        throw new AccessDeniedException();
    }

    /**
     * Checks the logged user belongs to a role and throws a not authorized
     * exception if not.
     *
     * @param string  $role     The role name
     *
     */
    protected function authorizeByRole($role)
    {
        if($this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY') )
        {
            if($this->container->get('security.context')->isGranted($role))
            {
                return;
            }
        }

        throw new AccessDeniedException();
    }
}
