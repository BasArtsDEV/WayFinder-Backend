<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

#[AsEntityAutocompleteField]
class UserAutoCompleteField extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => User::class,
            'searchable_fields' => ['username'],
            'label' => 'Sélectionnez un utilisateur',
            'choice_label' => 'username',
            'multiple' => true,
            'max_results' => 10,
            'max_options' => 10,
            'no_results_found_text' => 'Aucun utilisateur trouvé',
            'no_more_results_text' => 'Aucun autre résultat',
            'extra_options' => [],
            'constraints' => [
                new Count(min: 1, minMessage: 'Veuillez sélectionner au moins un utilisateur.'),
            ],
            'query_builder' => static function (Options $options) {
                $teamId = $options['extra_options']['team_id'] ?? null;

                return static fn (UserRepository $repository) => null !== $teamId
                    ? $repository->createQueryBuilderNotInTeam($teamId)
                    : $repository->createQueryBuilder('u')->where('1 = 0');
            },
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
