<?php

declare(strict_types=1);

namespace pocketmine\scheduler;

use pocketmine\Collectable;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use function is_scalar;
use function is_string;
use function igbinary_serialize;
use function igbinary_unserialize;

/**
 * Class used to run async tasks in other threads.
 *
 * An AsyncTask does not have its own thread. It is queued into an AsyncPool and executed if there is an async worker
 * with no AsyncTask running. Therefore, an AsyncTask SHOULD NOT execute for more than a few seconds. For tasks that
 * run for a long time or infinitely, start another thread instead.
 *
 * WARNING: Any non-Threaded objects WILL BE SERIALIZED when assigned to members of AsyncTasks or other Threaded object.
 * If later accessed from said Threaded object, you will be operating on a COPY OF THE OBJECT, NOT THE ORIGINAL OBJECT.
 * If you want to store non-serializable objects to access when the task completes, store them using
 * {@link AsyncTask::storeLocal}.
 *
 * WARNING: As of pthreads v3.1.6, arrays are converted to Volatile objects when assigned as members of Threaded objects.
 * Keep this in mind when using arrays stored as members of your AsyncTask.
 *
 * WARNING: Do not call PocketMine-MP API methods from other Threads!!
 */
abstract class AsyncTask extends Collectable{

	/**
	 * @var \SplObjectStorage|null
	 * @phpstan-var \SplObjectStorage<AsyncTask, mixed>
	 * Used to store objects on the main thread which should not be serialized.
	 */
	private static $threadLocalStorage;

	/** @var AsyncWorker|null $worker */
	public $worker = null;

	/** @var \Threaded */
	public $progressUpdates;

	/** @var scalar|null */
	private $result = null;
	private bool $serialized = false;
	private bool $cancelRun = false;
	private ?int $taskId = null;

	private bool $crashed = false;

	public function run() : void{
		$this->result = null;

		if(!$this->cancelRun){
			try{
				$this->onRun();
			}catch(\Throwable $e){
				$this->crashed = true;
				$this->worker->handleException($e);
			}
		}

		$this->setGarbage();
	}

	public function isCrashed() : bool{
		return $this->crashed or $this->isTerminated();
	}

	/**
	 * @return mixed
	 */
	public function getResult() : mixed{
		if($this->serialized){
			if(!is_string($this->result)){
				throw new AssumptionFailedError("Result expected to be a serialized string");
			}

			return igbinary_unserialize($this->result);
		}

		return $this->result;
	}

	/**
	 * @return void
	 */
	public function cancelRun(){
		$this->cancelRun = true;
	}

	public function hasCancelledRun() : bool{
		return $this->cancelRun;
	}

	public function hasResult() : bool{
		return $this->result !== null;
	}

	public function setResult(mixed $result) : void{
		$this->result = ($this->serialized = !is_scalar($result)) ? igbinary_serialize($result) : $result;
	}

	public function setTaskId(int $taskId) : void{
		$this->taskId = $taskId;
	}

	public function getTaskId() : ?int{
		return $this->taskId;
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::getFromThreadStore()
	 */
	public function getFromThreadStore(string $identifier) : mixed{
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects stored in AsyncWorker thread-local storage can only be retrieved during task execution");
		}
		return $this->worker->getFromThreadStore($identifier);
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::saveToThreadStore()
	 *
	 * @param mixed  $value
	 *
	 * @return void
	 */
	public function saveToThreadStore(string $identifier, $value){
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be added to AsyncWorker thread-local storage during task execution");
		}
		$this->worker->saveToThreadStore($identifier, $value);
	}

	/**
	 * @deprecated
	 * @see AsyncWorker::removeFromThreadStore()
	 */
	public function removeFromThreadStore(string $identifier) : void{
		if($this->worker === null or $this->isGarbage()){
			throw new \BadMethodCallException("Objects can only be removed from AsyncWorker thread-local storage during task execution");
		}

		$this->worker->removeFromThreadStore($identifier);
	}

	/**
	 * Actions to execute when run
	 */
	abstract public function onRun() : void;

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 */
	public function onCompletion(Server $server) : void{

	}

	/**
	 * Call this method from {@link AsyncTask::onRun} (AsyncTask execution thread) to schedule a call to
	 * {@link AsyncTask::onProgressUpdate} from the main thread with the given progress parameter.
	 *
	 * @param mixed $progress A value that can be safely serialize()'ed.
	 *
	 * @return void
	 */
	public function publishProgress($progress){
		$this->progressUpdates[] = igbinary_serialize($progress);
	}

	/**
	 * @internal Only call from AsyncPool.php on the main thread
	 *
	 * @return void
	 */
	public function checkProgressUpdates(Server $server){
		while($this->progressUpdates->count() !== 0){
			$progress = $this->progressUpdates->shift();
			$this->onProgressUpdate($server, igbinary_unserialize($progress));
		}
	}

	/**
	 * Called from the main thread after {@link AsyncTask::publishProgress} is called.
	 * All {@link AsyncTask::publishProgress} calls should result in {@link AsyncTask::onProgressUpdate} calls before
	 * {@link AsyncTask::onCompletion} is called.
	 *
	 * @param mixed  $progress The parameter passed to {@link AsyncTask::publishProgress}. It is serialize()'ed
	 *                         and then unserialize()'ed, as if it has been cloned.
	 *
	 * @return void
	 */
	public function onProgressUpdate(Server $server, $progress){

	}

	/**
	 * Saves mixed data in thread-local storage on the parent thread. You may use this to retain references to objects
	 * or arrays which you need to access in {@link AsyncTask::onCompletion} which cannot be stored as a property of
	 * your task (due to them becoming serialized).
	 *
	 * Scalar types can be stored directly in class properties instead of using this storage.
	 *
	 * WARNING: THIS METHOD SHOULD ONLY BE CALLED FROM THE MAIN THREAD!
	 *
	 * @param mixed $complexData the data to store
	 *
	 * @return void
	 * @throws \BadMethodCallException if called from any thread except the main thread
	 */
	protected function storeLocal($complexData){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be stored from the parent thread");
		}

		if(self::$threadLocalStorage === null){
			/** @phpstan-var \SplObjectStorage<AsyncTask, mixed> $storage */
			$storage = new \SplObjectStorage();
			self::$threadLocalStorage = $storage; //lazy init
		}

		if(isset(self::$threadLocalStorage[$this])){
			throw new \InvalidStateException("Already storing complex data for this async task");
		}
		self::$threadLocalStorage[$this] = $complexData;
	}

	/**
	 * Returns data previously stored in thread-local storage on the parent thread. Use this during progress updates or
	 * task completion to retrieve data you stored using {@link AsyncTask::storeLocal}.
	 *
	 * WARNING: THIS METHOD SHOULD ONLY BE CALLED FROM THE MAIN THREAD!
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException if no data were stored by this AsyncTask instance.
	 * @throws \BadMethodCallException if called from any thread except the main thread
	 */
	protected function fetchLocal(){
		if($this->worker !== null and $this->worker === \Thread::getCurrentThread()){
			throw new \BadMethodCallException("Objects can only be retrieved from the parent thread");
		}

		if(self::$threadLocalStorage === null or !isset(self::$threadLocalStorage[$this])){
			throw new \InvalidStateException("No complex data stored for this async task");
		}

		return self::$threadLocalStorage[$this];
	}

	/**
	 * @deprecated
	 * @see AsyncTask::fetchLocal()
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException if no data were stored by this AsyncTask instance
	 * @throws \BadMethodCallException if called from any thread except the main thread
	 */
	protected function peekLocal(){
		return $this->fetchLocal();
	}

	/**
	 * @internal Called by the AsyncPool to destroy any leftover stored objects that this task failed to retrieve.
	 */
	public function removeDanglingStoredObjects() : void{
		if(self::$threadLocalStorage !== null and isset(self::$threadLocalStorage[$this])){
			unset(self::$threadLocalStorage[$this]);
		}
	}
}