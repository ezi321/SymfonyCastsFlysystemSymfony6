<?php

namespace App\Controller;

use App\Entity\User;
use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @method User getUser()
 */
abstract class BaseController extends AbstractController
{
}