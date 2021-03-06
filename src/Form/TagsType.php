<?php

namespace AppBundle\Form;

use AppBundle\Entity\Tag;
use AppBundle\Entity\Model\TaggableInterface;
use AppBundle\Service\TagManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagsType extends AbstractType
{
    private $tagManager;
    private $objectManager;

    public function __construct(TagManager $tagManager, EntityManagerInterface $objectManager)
    {
        $this->tagManager = $tagManager;
        $this->objectManager = $objectManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $taggable = $form->getParent()->getData();

            if (!$taggable instanceof TaggableInterface) {
                return;
            }

            $tags = array_map(
                fn($tag) => $tag->getSlug(),
                iterator_to_array($taggable->getTags())
            );

            if (!empty($tags)) {
                $event->setData(implode(' ', $tags));
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

            $form = $event->getForm();
            $taggable = $form->getParent()->getData();

            if (!$taggable instanceof TaggableInterface) {
                return;
            }

            $tagsAsString = $event->getData();
            $slugs = explode(' ', $tagsAsString);

            $tags = $this->tagManager->fromSlugs($slugs);

            $taggable->setTags($tags);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $tags = $this->objectManager->getRepository(Tag::class)->findAll();

        $data = array_map(fn(Tag $tag) => [
            'id'    => $tag->getId(),
            'name'  => $tag->getName(),
            'slug'  => $tag->getSlug(),
            'color' => $tag->getColor(),
        ], $tags);

        $resolver->setDefaults(array(
            'attr' => [
                'data-tags' => json_encode($data)
            ]
        ));
    }

    public function getParent()
    {
        return TextType::class;
    }
}
