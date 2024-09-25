<?php declare(strict_types = 1);

namespace Awaitcz\DiAttribute\DI;

use Awaitcz\DiAttribute\DiService;
use Nette;
use Nette\Loaders\RobotLoader;
use ReflectionClass;
use stdClass;
use function array_filter;
use function class_exists;
use function sprintf;
use function str_starts_with;

/** @method stdClass getConfig() */
final class DiAttributeExtension extends Nette\DI\CompilerExtension
{

	/** @var array<int, string> */
	private array $discoveredServices = [];

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'paths' => Nette\Schema\Expect::arrayOf('string'),
			'excludes' => Nette\Schema\Expect::arrayOf('string'),
		]);
	}

	public function loadConfiguration(): void
	{
		$config = $this->getConfig();

		// Process resources
		$this->discoveredServices = $this->findClassesForRegistration(
			$config->paths,
			$config->excludes,
		);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// Register services of given resource
		$counter = 1;
		foreach ($this->discoveredServices as $class) {

			// Check already registered classes
			if ($builder->getByType($class) !== null) {
				continue;
			}

			$name = sprintf('service.%s', $counter++);
			$builder->addDefinition($this->prefix($name))
				->setFactory($class)
				->setType($class);
		}
	}

	/**
	 * @param array<string> $dirs
	 * @param array<string> $excludes
	 * @return array<string>
	 */
	protected function findClassesForRegistration(array $dirs, array $excludes = []): array
	{
		$loader = $this->createRobotLoader();
		$loader->addDirectory(...$dirs);
		$loader->rebuild();

		$indexed = $loader->getIndexedClasses();

		$classes = [];
		foreach ($indexed as $class => $file) {

			// Excluded namespace
			if (array_filter(
				$excludes,
				static fn (string $exclude): bool => str_starts_with($class, $exclude),
			) !== []) {
				continue;
			}

			// Skip not existing class
			if (!class_exists($class)) {
				continue;
			}

			// Detect by reflection
			$ct = new ReflectionClass($class);

			// Skip abstract
			if ($ct->isAbstract()) {
				continue;
			}

			foreach ($ct->getAttributes() as $attribute) {
				$attributeInstance = $attribute->newInstance();

				if (!($attributeInstance instanceof DiService)) {
					continue;
				}

				$classes[] = $class;
			}
		}

		return $classes;
	}

	protected function createRobotLoader(): RobotLoader
	{
		if (!class_exists(RobotLoader::class)) {
			throw new Nette\InvalidStateException('Install nette/robot-loader at first');
		}

		return new RobotLoader();
	}

}
