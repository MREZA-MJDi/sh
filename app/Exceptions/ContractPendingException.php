<?php

namespace App\Exceptions;

use RuntimeException;

/** وقتی قرارداد/API رسمی سرویس‌دهنده هنوز تحویل نشده است. */
class ContractPendingException extends RuntimeException
{
}
