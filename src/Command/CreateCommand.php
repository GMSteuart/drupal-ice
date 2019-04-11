<?php

namespace Drupal\ice\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\consumers\Entity\Consumer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Console\Utils\DrupalApi;

/**
 * Class CreateCommand.
 *
 * @DrupalCommand (
 *     extension="ice",
 *     extensionType="module"
 * )
 */
class CreateCommand extends ContainerAwareCommand {

  /**
   * @var Connection
   */
  protected $database;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var DrupalApi
   */
  protected $drupalApi;

  /**
   * CreateCommand constructor.
   *
   * @param Connection                 $database
   * @param EntityTypeManagerInterface $entityTypeManager
   * @param DrupalApi                  $drupalApi
   */
  public function __construct(
      Connection $database,
      EntityTypeManagerInterface $entityTypeManager,
      DrupalApi $drupalApi
  ) {
      $this->database = $database;
      $this->entityTypeManager = $entityTypeManager;
      $this->drupalApi = $drupalApi;
      parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('consumer:create')
      ->setDescription($this->trans('commands.consumer.create.description'))
      ->addArgument(
        'label',
        InputArgument::REQUIRED,
        $this->trans('commands.user.create.options.label')
      )
      ->addOption(
        'uid',
        null,
        InputArgument::OPTIONAL,
        $this->trans('commands.user.create.options.uid')
      )
      ->addOption(
        'owner',
        null,
        InputArgument::OPTIONAL,
        $this->trans('commands.user.create.options.owner')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $label = $input->getArgument('label');
    $owner = $input->getOption('owner');

    $consumer = $this->createConsumer(
      $label,
      $owner
    );

    if ($consumer['success']) {
      // TODO display table of added information
      $this->getIo()->success(
        sprintf(
            $this->trans('commands.consumer.create.messages.success'),
            $consumer['success']['label']
        )
      );

      return 0;
    }

    if ($consumer['error']) {
      $this->getIo()->error($consumer['error']['error']);
      return 1;
    }
  }

  private function createConsumer($label, $owner = null) {
    // $consumer = new Consumer([
    //   'label' => $label,
    //   'user_id' => 24, // user to link to
    //   'third_party' => false,
    //   'roles' => "nuxt"
    // ], "consumer");
    // dump($consumer->getFieldDefinitions());
    // exit;
    // TODO implement third_party, description,
    // TODO make uid a passable parameter
    // TODO allow for uid to be a string => username or integer => uid
    $consumer = Consumer::create([
      'label' => $label,
      'user_id' => 24, // user to link to
      'third_party' => false,
      'roles' => "nuxt"
    ]);

    $result = [];

    try {
      $consumer->save();

      $result['success'] = [
        'id' => $consumer->id(),
        'label' => $consumer->get('label'),
        'owner' => $consumer->getOwnerId()
      ];

    } catch (\Exception $e) {
      $result['error'] = [
        'id' => $consumer->id(),
        'label' => $consumer->get('label'),
        'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
      ];
    }

    return $result;
  }
}
