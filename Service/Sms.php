<?php

namespace SmartInformationSystems\SmsBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\EngineInterface;
use Doctrine\ORM\EntityManager;
use Twig\Environment;

use SmartInformationSystems\SmsBundle\Transport\AbstractTransport;
use SmartInformationSystems\SmsBundle\Transport\TransportFactory;
use SmartInformationSystems\SmsBundle\Transport\ConfigurationContainer;
use SmartInformationSystems\SmsBundle\Entity\Sms as SmsEntity;

/**
 * Сервис для отправки смс.
 *
 */
class Sms
{
    /**
     * @var AbstractTransport
     */
    private $transport;

    /**
     * От кого отправлять сообщения.
     *
     * @var string
     */
    private $defaultFrom;

    public function __construct(
        private ConfigurationContainer $configuration,
        private ContainerInterface $container,
        private Environment $templating,
        private EntityManagerInterface $entityManager
    )
    {

        $this->defaultFrom = $configuration->getFrom();

        $this->transport = TransportFactory::create(
            $configuration,
            $this->entityManager
        );
    }

    /**
     * Отправка смс.
     *
     * @param string $phone Кому
     * @param string $template Шаблон
     * @param array $templateVars Переменные шаблона
     * @param string $fromName От кого
     *
     * @return SmsEntity
     *
     * @throws
     */
    public function send($phone, $template, array $templateVars = [], $fromName = '')
    {
        $sms = new SmsEntity();
        $sms
            ->setTransport($this->getTransport()->getName())
            ->setFromName($fromName ? $fromName : $this->defaultFrom)
            ->setPhone($phone)
            ->setMessage(
                $this->templating->render(
                    $template,
                    $templateVars
                )
            );

        /** @var EntityManager $em */
        $em = $this->entityManager;
        $em->persist($sms);
        $em->flush($sms);

        return $sms;
    }

    /**
     * Возвращает траспорт.
     *
     * @return AbstractTransport
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
