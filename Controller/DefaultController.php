<?php
/**
 * (c) 2011-2012 Vespolina Project http://www.vespolina-project.org
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Vespolina\CartBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Vespolina\Entity\CartInterface;
use Vespolina\CartBundle\Form\Cart as CartForm;
use Vespolina\StoreBundle\Controller\AbstractController;

/**
 * @author Richard D Shank <develop@zestic.com>
 */
class DefaultController extends AbstractController
{
    public function quickInspectionAction()
    {
        $cartManager = $this->container->get('vespolina.cart_manager');
        $cart = $this->getCart();

        $totalPrice = $cart->getPricing()->get('totalGross');

        return $this->render('VespolinaCartBundle:Default:quickInspection.html.twig', array('cart' => $cart, 'totalPrice' => $totalPrice ));
    }

    public function addToCartAction($productId, $cartId = null)
    {
        $cart = $this->getCart($cartId);
        $product = $this->findProductById($productId);
        $cartMgr = $this->container->get('vespolina.cart_manager');

        $cartMgr->addProductToCart($cart, $product);
        $cartMgr->updateCart($cart);

        return new RedirectResponse($this->container->get('router')->generate('vespolina_cart_show', array('cartId' => $cartId)));
    }

    public function removeFromCartAction($productId, $cartId = null)
    {
        $cart = $this->getCart($cartId);
        $product = $this->findProductById($productId);
        $cartMgr = $this->container->get('vespolina.cart_manager');

        $cartMgr->removeProductFromCart($cart, $product);
        $cartMgr->updateCart($cart);

        return new RedirectResponse($this->container->get('router')->generate('vespolina_cart_show', array('cartId' => $cartId)));
    }

    public function updateCartAction($cartId = null)
    {
        $request = $this->container->get('request');
        if ($request->getMethod() == 'POST') {
            $cartMgr = $this->container->get('vespolina.cart_manager');
            $cart = $this->getCart($cartId);
            $data = $request->get('cart');
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = $this->findProductById($item['product']['id']);
                    if ($item['quantity'] < 1) {
                        $cartMgr->removeProductFromCart($cart, $product);
                    } elseif ($cartItem = $this->container->get('vespolina.cart_manager')->findProductInCart($cart, $product)) {
                        $cartMgr->setItemQuantity($cartItem, $item['quantity']);
                    }
                }
            }

            $cartMgr->updateCart($cart);
        }

        return new RedirectResponse($this->container->get('router')->generate('vespolina_cart_show', array('cartId' => $cartId)));
    }

    public function showAction($cartId = null)
    {
        $cart = $this->getCart($cartId);
        $form = $this->container->get('form.factory')->create(new CartForm(), $cart);

        $template = $this->container->get('templating')->render(sprintf('VespolinaCartBundle:Default:show.html.%s', $this->getEngine()), array('cart' => $cart, 'form' => $form->createView()));

        return new Response($template);
    }

    protected function findProductById($productId)
    {
        return $this->container->get('vespolina.product_manager')->findProductById($productId);
    }

    protected function getCart($cartId = null)
    {
        if ($cartId) {
            return $this->container->get('vespolina.cart_manager')->findCartById($cartId);
        } else {
            return $this->container->get('vespolina.cart_manager')->getActiveCart();
        }
    }

    protected function getEngine()
    {
        return $this->container->getParameter('vespolina_cart.template.engine');
    }
}
