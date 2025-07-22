# Events

This guide explains the event handling system in PurePHP.

## Basic Events

PurePHP supports basic HTML events through props.

```php
<?php

use function Pure\HTML\{button, input};

function BasicEvents() {
    return div(
        button('Click me')
            ->onclick('handleClick()')
            ->class('btn'),
        input()
            ->type('text')
            ->oninput('handleInput(event)')
            ->class('input')
    )->class('events-container');
}

// Use the component
BasicEvents()->toPrint();
```

## Mouse Events

PurePHP supports various mouse events.

```php
<?php

use function Pure\HTML\{div};

function MouseEvents() {
    return div()
        ->onclick('handleClick(event)')
        ->onmouseover('handleMouseOver(event)')
        ->onmouseout('handleMouseOut(event)')
        ->onmousedown('handleMouseDown(event)')
        ->onmouseup('handleMouseUp(event)')
        ->class('mouse-events');
}

// Use the component
MouseEvents()->toPrint();
```

## Keyboard Events

PurePHP supports keyboard events.

```php
<?php

use function Pure\HTML\{input};

function KeyboardEvents() {
    return input()
        ->type('text')
        ->onkeydown('handleKeyDown(event)')
        ->onkeyup('handleKeyUp(event)')
        ->onkeypress('handleKeyPress(event)')
        ->class('keyboard-events');
}

// Use the component
KeyboardEvents()->toPrint();
```

## Form Events

PurePHP supports form events.

```php
<?php

use function Pure\HTML\{form, input, button};

function FormEvents() {
    return form(
        input()
            ->type('text')
            ->name('username')
            ->onchange('handleChange(event)'),
        button('Submit')
            ->type('submit')
            ->onclick('handleSubmit(event)')
    )
    ->onsubmit('handleFormSubmit(event)')
    ->class('form-events');
}

// Use the component
FormEvents()->toPrint();
```

## Event Objects

PurePHP provides access to event objects.

```php
<?php

use function Pure\HTML\{div, button};

function EventObjects() {
    return div(
        button('Click me')
            ->onclick('handleClick(event)')
            ->class('btn')
    )->class('event-objects');
}

// JavaScript event handler
?>
<script>
function handleClick(event) {
    // Access event properties
    console.log('Event type:', event.type);
    console.log('Target element:', event.target);
    console.log('Mouse coordinates:', event.clientX, event.clientY);
    console.log('Timestamp:', event.timeStamp);
}
</script>
```

## Custom Events

PurePHP supports custom events.

```php
<?php

use function Pure\HTML\{div, button};

function CustomEvents() {
    return div(
        button('Trigger Event')
            ->onclick('triggerCustomEvent()')
            ->class('btn')
    )
    ->on('customEvent', 'handleCustomEvent(event)')
    ->class('custom-events');
}

// JavaScript event handling
?>
<script>
function triggerCustomEvent() {
    const event = new CustomEvent('customEvent', {
        detail: { message: 'Hello from custom event!' }
    });
    document.querySelector('.custom-events').dispatchEvent(event);
}

function handleCustomEvent(event) {
    console.log('Custom event data:', event.detail);
}
</script>
```

## Event Delegation

PurePHP supports event delegation.

```php
<?php

use function Pure\HTML\{div, button};

function EventDelegation() {
    return div(
        foreach (range(1, 5) as $i) {
            button("Button {$i}")
                ->data('id', $i)
                ->class('delegated-btn')
        }
    )
    ->onclick('handleDelegatedClick(event)')
    ->class('delegation-container');
}

// JavaScript event delegation
?>
<script>
function handleDelegatedClick(event) {
    if (event.target.classList.contains('delegated-btn')) {
        const buttonId = event.target.dataset.id;
        console.log('Clicked button:', buttonId);
    }
}
</script>
```

## Event Bus

PurePHP supports an event bus pattern for component communication.

```php
<?php

use function Pure\HTML\{div, button};

class EventBus {
    private static $listeners = [];

    public static function on($event, $callback) {
        if (!isset(self::$listeners[$event])) {
            self::$listeners[$event] = [];
        }
        self::$listeners[$event][] = $callback;
    }

    public static function emit($event, $data) {
        if (isset(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $callback) {
                $callback($data);
            }
        }
    }
}

function EventBusExample() {
    return div(
        button('Emit Event')
            ->onclick('EventBus.emit("customEvent", { message: "Hello!" })')
            ->class('btn')
    )->class('event-bus');
}

// JavaScript event bus usage
?>
<script>
EventBus.on('customEvent', (data) => {
    console.log('Received event:', data);
});
</script>
```

## Next Steps

- [Components](/guide/components) - Learn more about component development
- [Props](/guide/props) - Understand props system
