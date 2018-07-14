<?php

namespace ingelby\toolbox\constants;

class HttpStatus
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const NO_CONTENT = 204;

    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    const CONFLICT = 409;
    const IM_A_TEA_POT = 418;

    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;

    const NO_CONTENT_MESSAGE = 'No content';
    const UNAUTHORIZED_MESSAGE = 'You are not authorized to access this';
    const NOT_IMPLEMENTED_MESSAGE = 'Method not implemented';
}