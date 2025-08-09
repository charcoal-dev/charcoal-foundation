<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Input;

/**
 * Class InputParamAttribute
 * @package App\Shared\Core\Http\Request\Input
 */
class InputParamAttribute
{
    private int $flag = 0;

    /**
     * @param InputParamEnumInterface $enum
     * @param string $key
     */
    public function __construct(
        public readonly InputParamEnumInterface $enum,
        public readonly string                  $key,
    )
    {
    }

    /**
     * @param ParamFlag $flag
     * @return $this
     */
    public function set(ParamFlag $flag): static
    {
        $this->flag = $this->flag | $flag->value;
        return $this;
    }

    /**
     * @param ParamFlag $flag
     * @return bool
     */
    public function has(ParamFlag $flag): bool
    {
        return (bool)(($this->flag & $flag->value));
    }

    /**
     * @param ParamFlag $flag
     * @return $this
     */
    public function remove(ParamFlag $flag): static
    {
        $this->flag &= ~$flag->value;
        return $this;
    }
}