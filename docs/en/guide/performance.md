# Performance

This guide explains performance optimization techniques in PurePHP.

## Virtual DOM Optimization

PurePHP uses a Virtual DOM to optimize rendering performance.

### Batch Updates

```php
<?php

use function Pure\HTML\{div};

class OptimizedComponent {
    private $updates = [];

    public function queueUpdate($update) {
        $this->updates[] = $update;
    }

    public function applyUpdates() {
        // Apply all updates in a single batch
        foreach ($this->updates as $update) {
            $update();
        }
        $this->updates = [];
    }

    public function render() {
        return div(
            foreach ($this->updates as $update) {
                $update();
            }
        )->class('optimized-component');
    }
}

// Use the component
$component = new OptimizedComponent();
$component->queueUpdate(function() {
    echo "Update 1";
});
$component->queueUpdate(function() {
    echo "Update 2";
});
$component->applyUpdates();
```

### Diff Comparison

```php
<?php

use function Pure\HTML\{div};

class DiffComponent {
    private $prevProps = null;

    public function shouldUpdate($newProps) {
        if ($this->prevProps === null) {
            $this->prevProps = $newProps;
            return true;
        }

        // Only update if props have changed
        $shouldUpdate = $this->prevProps !== $newProps;
        $this->prevProps = $newProps;
        return $shouldUpdate;
    }

    public function render($props) {
        if (!$this->shouldUpdate($props)) {
            return null; // Skip rendering if props haven't changed
        }

        return div(
            foreach ($props as $key => $value) {
                div("{$key}: {$value}")->class('prop-item');
            }
        )->class('diff-component');
    }
}

// Use the component
$component = new DiffComponent();
$component->render(['name' => 'John', 'age' => 30]);
```

## Component Optimization

### Component Caching

```php
<?php

use function Pure\HTML\{div};

class CachedComponent {
    private $cache = [];
    private $cacheTimeout = 3600; // 1 hour

    public function getCached($key) {
        if (isset($this->cache[$key])) {
            $cached = $this->cache[$key];
            if (time() - $cached['time'] < $this->cacheTimeout) {
                return $cached['data'];
            }
        }
        return null;
    }

    public function setCached($key, $data) {
        $this->cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }

    public function render($props) {
        $cacheKey = md5(json_encode($props));
        $cached = $this->getCached($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = div(
            foreach ($props as $key => $value) {
                div("{$key}: {$value}")->class('prop-item');
            }
        )->class('cached-component');

        $this->setCached($cacheKey, $result);
        return $result;
    }
}

// Use the component
$component = new CachedComponent();
$component->render(['name' => 'John', 'age' => 30]);
```

### Lazy Loading

```php
<?php

use function Pure\HTML\{div};

class LazyComponent {
    private $loaded = false;
    private $data = null;

    public function loadData() {
        if (!$this->loaded) {
            // Simulate API call
            $this->data = ['items' => [1, 2, 3]];
            $this->loaded = true;
        }
        return $this->data;
    }

    public function render() {
        if (!$this->loaded) {
            return div('Loading...')->class('loading');
        }

        $data = $this->loadData();
        return div(
            foreach ($data['items'] as $item) {
                div("Item: {$item}")->class('item');
            }
        )->class('lazy-component');
    }
}

// Use the component
$component = new LazyComponent();
$component->render();
```

## Render Optimization

### Conditional Rendering

```php
<?php

use function Pure\HTML\{div};

function OptimizedRender($props) {
    [
        'isLoading' => $isLoading,
        'error' => $error,
        'data' => $data
    ] = $props;

    if ($isLoading) {
        return div('Loading...')->class('loading');
    }

    if ($error) {
        return div("Error: {$error}")->class('error');
    }

    return div(
        foreach ($data as $item) {
            div("Item: {$item}")->class('item');
        }
    )->class('optimized-render');
}

// Use the component
OptimizedRender([
    'isLoading' => false,
    'error' => null,
    'data' => [1, 2, 3]
])->toPrint();
```

### List Rendering Optimization

```php
<?php

use function Pure\HTML\{div};

function OptimizedList($props) {
    [
        'items' => $items,
        'pageSize' => $pageSize = 10,
        'currentPage' => $currentPage = 1
    ] = $props;

    $start = ($currentPage - 1) * $pageSize;
    $visibleItems = array_slice($items, $start, $pageSize);

    return div(
        foreach ($visibleItems as $item) {
            div("Item: {$item}")->class('list-item');
        }
    )->class('optimized-list');
}

// Use the component
OptimizedList([
    'items' => range(1, 100),
    'pageSize' => 10,
    'currentPage' => 1
])->toPrint();
```

## Memory Optimization

### Resource Release

```php
<?php

use function Pure\HTML\{div};

class ResourceComponent {
    private $resources = [];

    public function __construct() {
        // Initialize resources
        $this->resources['db'] = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
        $this->resources['cache'] = new Memcached();
    }

    public function render() {
        return div('Resource Component')->class('resource-component');
    }

    public function __destruct() {
        // Release resources
        foreach ($this->resources as $resource) {
            if ($resource instanceof PDO) {
                $resource = null;
            } elseif ($resource instanceof Memcached) {
                $resource->quit();
            }
        }
        $this->resources = [];
    }
}

// Use the component
$component = new ResourceComponent();
$component->render();
unset($component);
```

### Circular Reference Handling

```php
<?php

use function Pure\HTML\{div};

class CircularComponent {
    private $parent = null;
    private $children = [];

    public function setParent($parent) {
        $this->parent = $parent;
    }

    public function addChild($child) {
        $this->children[] = $child;
        $child->setParent($this);
    }

    public function render() {
        return div(
            foreach ($this->children as $child) {
                $child->render();
            }
        )->class('circular-component');
    }

    public function __destruct() {
        // Break circular references
        $this->parent = null;
        $this->children = [];
    }
}

// Use the component
$parent = new CircularComponent();
$child = new CircularComponent();
$parent->addChild($child);
$parent->render();
unset($parent);
unset($child);
```

## Cache Optimization

### Component Cache

```php
<?php

use function Pure\HTML\{div};

class ComponentCache {
    private static $cache = [];
    private static $timeout = 3600; // 1 hour

    public static function get($key) {
        if (isset(self::$cache[$key])) {
            $cached = self::$cache[$key];
            if (time() - $cached['time'] < self::$timeout) {
                return $cached['data'];
            }
        }
        return null;
    }

    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }

    public static function clear() {
        self::$cache = [];
    }
}

function CachedComponent($props) {
    $cacheKey = md5(json_encode($props));
    $cached = ComponentCache::get($cacheKey);

    if ($cached !== null) {
        return $cached;
    }

    $result = div(
        foreach ($props as $key => $value) {
            div("{$key}: {$value}")->class('prop-item');
        }
    )->class('cached-component');

    ComponentCache::set($cacheKey, $result);
    return $result;
}

// Use the component
CachedComponent(['name' => 'John', 'age' => 30])->toPrint();
```

### Data Cache

```php
<?php

use function Pure\HTML\{div};

class DataCache {
    private static $cache = [];
    private static $timeout = 3600; // 1 hour

    public static function get($key) {
        if (isset(self::$cache[$key])) {
            $cached = self::$cache[$key];
            if (time() - $cached['time'] < self::$timeout) {
                return $cached['data'];
            }
        }
        return null;
    }

    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'time' => time()
        ];
    }

    public static function clear() {
        self::$cache = [];
    }
}

function DataComponent($props) {
    [
        'id' => $id
    ] = $props;

    $cacheKey = "data_{$id}";
    $data = DataCache::get($cacheKey);

    if ($data === null) {
        // Simulate API call
        $data = ['name' => 'John', 'age' => 30];
        DataCache::set($cacheKey, $data);
    }

    return div(
        foreach ($data as $key => $value) {
            div("{$key}: {$value}")->class('data-item');
        }
    )->class('data-component');
}

// Use the component
DataComponent(['id' => 1])->toPrint();
```

## Next Steps

- [Components](/en/guide/components) - Learn more about component development
- [Props](/en/guide/props) - Understand props system
- [Events](/en/guide/events) - Learn about event handling
