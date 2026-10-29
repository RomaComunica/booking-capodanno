<?php

namespace Tests\Unit;

use App\Models\Partner;
use PHPUnit\Framework\TestCase;

class PartnerProvvigioneTest extends TestCase
{
    public function test_solo_agenzia_e_affiliato_prendono_provvigione(): void
    {
        $this->assertTrue(Partner::tipoPrendeProvvigione(Partner::TIPO_AGENZIA));
        $this->assertTrue(Partner::tipoPrendeProvvigione(Partner::TIPO_AFFILIATO));
        $this->assertFalse(Partner::tipoPrendeProvvigione(Partner::TIPO_DIRETTO));
        $this->assertFalse(Partner::tipoPrendeProvvigione(0));
        $this->assertFalse(Partner::tipoPrendeProvvigione(null));
    }

    public function test_calcolo_percentuale_e_fisso(): void
    {
        $this->assertEquals(20.0, Partner::calcolaImportoProvvigione(Partner::TIPO_AGENZIA, 200, 2, 1, 10));
        $this->assertEquals(10.0, Partner::calcolaImportoProvvigione(Partner::TIPO_AFFILIATO, 200, 2, 0, 5));
        $this->assertEquals(0.0, Partner::calcolaImportoProvvigione(Partner::TIPO_DIRETTO, 200, 2, 1, 10));
        $this->assertEquals(0.0, Partner::calcolaImportoProvvigione(0, 200, 2, 1, 10));
    }

    public function test_etichette_tipo(): void
    {
        $this->assertSame('Agenzia', Partner::labelTipo(1));
        $this->assertSame('Affiliato', Partner::labelTipo(2));
        $this->assertSame('Diretto', Partner::labelTipo(3));
        $this->assertSame('Diretto', Partner::labelTipo(0));
        $this->assertSame(Partner::TIPO_DIRETTO, Partner::normalizzaTipo(0));
    }
}
