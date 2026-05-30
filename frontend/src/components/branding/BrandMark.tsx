import { cn } from '@/lib/cn';
import { useClientBrand } from '@/providers/ClientBrandingProvider';

interface BrandMarkProps {
  className?: string;
  variant?: 'sidebar' | 'login';
}

export function BrandMark({ className, variant = 'sidebar' }: BrandMarkProps) {
  const { brandName, logoUrl } = useClientBrand();

  if (logoUrl) {
    return (
      <img
        src={logoUrl}
        alt={brandName}
        className={cn(
          'object-contain object-left',
          variant === 'sidebar' ? 'h-8 max-w-[10rem]' : 'mx-auto h-10 max-w-[12rem]',
          className,
        )}
      />
    );
  }

  return (
    <span
      className={cn(
        'font-semibold tracking-tight',
        variant === 'sidebar' ? 'text-lg text-white' : 'text-xs uppercase tracking-widest text-primary',
        className,
      )}
    >
      {brandName}
    </span>
  );
}
