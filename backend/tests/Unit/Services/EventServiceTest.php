<?php

use App\Models\Event;
use App\Models\User;
use App\Services\EventService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

beforeEach(function () {
    $this->service = new EventService();
});

describe('EventService::list', function () {
    it('returns paginated results', function () {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orderBy')->with('time', 'desc')->andReturnSelf();
        $query->shouldReceive('paginate')->with(15)->andReturn($paginator);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->list();

        expect($result)->toBe($paginator);
    });

    it('applies search filter', function () {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('time', 'desc')->andReturnSelf();
        $query->shouldReceive('paginate')->with(15)->andReturn($paginator);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->list(['search' => 'cardiac']);

        expect($result)->toBe($paginator);
    });

    it('applies date range filters', function () {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->with('time', '>=', '2026-01-01')->once()->andReturnSelf();
        $query->shouldReceive('where')->with('time', '<=', '2026-12-31')->once()->andReturnSelf();
        $query->shouldReceive('orderBy')->with('time', 'desc')->andReturnSelf();
        $query->shouldReceive('paginate')->with(15)->andReturn($paginator);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->list([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        expect($result)->toBe($paginator);
    });

    it('respects per_page filter capped at 100', function () {
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('orderBy')->with('time', 'desc')->andReturnSelf();
        $query->shouldReceive('paginate')->with(100)->andReturn($paginator);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->list(['per_page' => 500]);

        expect($result)->toBe($paginator);
    });
});

describe('EventService::create', function () {
    it('creates an event and loads relationships', function () {
        $eventMock = Mockery::mock(Event::class);
        $eventMock->shouldReceive('load')
            ->with(['teamMembers', 'patients'])
            ->once()
            ->andReturnSelf();

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('create')
            ->with(['title' => 'Tumor Board', 'time' => '2026-04-01 09:00:00'])
            ->once()
            ->andReturn($eventMock);

        $result = $this->service->create([
            'title' => 'Tumor Board',
            'time' => '2026-04-01 09:00:00',
        ]);

        expect($result)->toBe($eventMock);
    });
});

describe('EventService::update', function () {
    it('updates an event and reloads relationships', function () {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('update')
            ->with(['title' => 'Updated Title'])
            ->once();
        $event->shouldReceive('load')
            ->with(['teamMembers', 'patients'])
            ->once()
            ->andReturnSelf();

        $result = $this->service->update($event, ['title' => 'Updated Title']);

        expect($result)->toBe($event);
    });
});

describe('EventService::delete', function () {
    it('deletes an event', function () {
        $event = Mockery::mock(Event::class);
        $event->shouldReceive('delete')->once();

        $this->service->delete($event);

        // If we get here without exception, the test passes
        expect(true)->toBeTrue();
    });
});

describe('EventService::getUpcoming', function () {
    it('returns upcoming events ordered by time ascending', function () {
        $collection = Mockery::mock(Collection::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->with('time', '>=', Mockery::any())->andReturnSelf();
        $query->shouldReceive('orderBy')->with('time', 'asc')->andReturnSelf();
        $query->shouldReceive('limit')->with(5)->andReturnSelf();
        $query->shouldReceive('get')->andReturn($collection);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->getUpcoming();

        expect($result)->toBe($collection);
    });

    it('accepts a custom limit', function () {
        $collection = Mockery::mock(Collection::class);

        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('orderBy')->andReturnSelf();
        $query->shouldReceive('limit')->with(10)->andReturnSelf();
        $query->shouldReceive('get')->andReturn($collection);

        $mock = Mockery::mock('alias:' . Event::class);
        $mock->shouldReceive('with')->with(['teamMembers', 'patients'])->andReturn($query);

        $result = $this->service->getUpcoming(10);

        expect($result)->toBe($collection);
    });
});
