# Error Codes

## Engine
Blocks `0x01` to `0xBF` are reserved by the engine.

| Code             | Error Constant            |
|------------------|---------------------------|
| `0x01`           | `MODULE_NOT_LOADED`       |
| `0x02`           | `CALL_TO_UNLOADED_MODULE` |
| `0x03` to `0xBF` | _Reserved for future use_ |

## Core
Blocks `0xC0` to `0xDF` are reserved by the core.

| Code             |                           |
|------------------|---------------------------|
| `0xC0` to `0xDF` | _Reserved for future use_ |

## Core Modules
Modules must update the following table. Modules may not reassign error codes of
another module. Any conflicts may be resolved by pull request reviewers.
