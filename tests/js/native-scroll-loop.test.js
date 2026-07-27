'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const modulePath = path.resolve(__dirname, '../../assets/js/native-scroll-loop.js');

test('frontend module exists', () => {
  assert.equal(fs.existsSync(modulePath), true);
});

if (fs.existsSync(modulePath)) {
  const utilities = require(modulePath);

  test('clamps numeric values', () => {
    assert.equal(utilities.clamp(-2, 0, 10), 0);
    assert.equal(utilities.clamp(12, 0, 10), 10);
    assert.equal(utilities.clamp(4, 0, 10), 4);
  });

  test('calculates normalized progress', () => {
    assert.equal(utilities.calculateProgress(50, 100), 0.5);
    assert.equal(utilities.calculateProgress(-10, 100), 0);
    assert.equal(utilities.calculateProgress(120, 100), 1);
    assert.equal(utilities.calculateProgress(0, 0), 1);
  });

  test('converts RTL scroll models to and from logical positions', () => {
    const maximum = 300;

    for (const type of ['negative', 'reverse', 'default']) {
      for (const logical of [0, 125, maximum]) {
        const raw = utilities.logicalToRaw(logical, maximum, true, type);
        assert.equal(utilities.rawToLogical(raw, maximum, true, type), logical);
      }
    }

    assert.equal(utilities.logicalToRaw(125, maximum, false, 'default'), 125);
  });

  test('calculates item and visible-group advance distances', () => {
    assert.equal(utilities.getVisibleItemsCount(640, 200, 20), 3);
    assert.equal(utilities.getAdvanceDistance('item', 640, 200, 20), 220);
    assert.equal(utilities.getAdvanceDistance('group', 640, 200, 20), 660);
  });

  test('calculates item width from Loop Grid columns and gap', () => {
    assert.equal(utilities.calculateItemWidth(640, 3, 20), 200);
    assert.equal(utilities.calculateItemWidth(640, 1, 20), 640);
    assert.equal(utilities.calculateItemWidth(100, 3, 80), 0);
  });

  test('builds informational dot targets from reachable scroll positions', () => {
    assert.deepEqual(utilities.getDotTargets(600, 200), [0, 200, 400, 600]);
    assert.deepEqual(utilities.getDotTargets(650, 200), [0, 200, 400, 600, 650]);
    assert.deepEqual(utilities.getDotTargets(0, 200), [0]);
    assert.deepEqual(utilities.getDotTargets(600, 0), [0, 600]);
  });

  test('selects the closest informational dot for the current scroll position', () => {
    const targets = [0, 200, 400, 600];

    assert.equal(utilities.getClosestTargetIndex(0, targets), 0);
    assert.equal(utilities.getClosestTargetIndex(275, targets), 1);
    assert.equal(utilities.getClosestTargetIndex(350, targets), 2);
    assert.equal(utilities.getClosestTargetIndex(600, targets), 3);
  });

  test('wraps manual navigation only when endpoint wrapping is enabled', () => {
    assert.equal(utilities.getNavigationTarget(1, 300, 300, 100, true), 0);
    assert.equal(utilities.getNavigationTarget(-1, 0, 300, 100, true), 300);
    assert.equal(utilities.getNavigationTarget(1, 300, 300, 100, false), 300);
    assert.equal(utilities.getNavigationTarget(-1, 0, 300, 100, false), 0);
    assert.equal(utilities.getNavigationTarget(1, 100, 300, 100, true), 200);
  });

  test('initializes existing enabled wrappers when handler registration is late', () => {
    const wrappers = [
      { getAttribute: () => 'loop-grid.post' },
      { getAttribute: () => 'loop-grid.product' },
    ];
    const initialized = [];
    let queriedSelector = '';
    const windowObject = {
      document: {
        querySelectorAll: (selector) => {
          queriedSelector = selector;
          return wrappers;
        },
      },
      jQuery: (wrapper) => ({ 0: wrapper }),
    };

    class Handler {
      constructor(options) {
        initialized.push(options);
      }
    }

    assert.equal(utilities.initializeExistingWidgets(windowObject, Handler), 2);
    assert.deepEqual(initialized.map(({ elementName }) => elementName), ['loop-grid.post', 'loop-grid.product']);
    assert.equal(initialized[0].$element[0], wrappers[0]);
    assert.equal(queriedSelector, '[data-widget_type^="loop-grid."]');
  });

  test('reads configuration from inner marker when editor strips wrapper attributes', () => {
    const marker = {
      getAttribute: () => '{"enabled":true,"arrowPosition":"split-sides"}',
    };
    const root = {
      getAttribute: () => null,
      querySelector: (selector) => selector === '[data-native-scroll-loop-config]' ? marker : null,
    };

    assert.deepEqual(utilities.readConfigFromRoot(root), {
      enabled: true,
      arrowPosition: 'split-sides',
    });
  });

  test('parses configuration defensively', () => {
    assert.deepEqual(utilities.parseConfig('{"enabled":true}'), { enabled: true });
    assert.deepEqual(utilities.parseConfig('invalid'), {});
    assert.deepEqual(utilities.parseConfig(null), {});
  });
}
